<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoPersonal extends Model
{
    use HasFactory;

    protected $table = 'movimientos_personal';

    public const TIPOS = [
        'activo',
        'baja',
        'licencia',
        'suspendido',
        'reingreso',
    ];

    protected $fillable = [
        'persona_id',
        'tipo',
        'fecha',
        'motivo',
        'observaciones',
        'registrado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
