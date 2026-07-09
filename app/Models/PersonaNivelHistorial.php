<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonaNivelHistorial extends Model
{
    protected $table = 'persona_nivel_historial';

    protected $fillable = [
        'persona_nivel_id',
        'persona_nivel_detalle_id',
        'persona_id',
        'nivel_id',
        'accion',
        'descripcion',
        'datos_anteriores',
        'datos_nuevos',
        'usuario_id',
        'fecha',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'fecha' => 'datetime',
    ];

    public function cabecera()
    {
        return $this->belongsTo(PersonaNivel::class, 'persona_nivel_id');
    }

    public function detalle()
    {
        return $this->belongsTo(PersonaNivelDetalle::class, 'persona_nivel_detalle_id');
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
