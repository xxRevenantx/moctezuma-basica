<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TallerSesion extends Model
{
    use HasFactory;

    protected $table = 'taller_sesiones';

    protected $fillable = [
        'taller_id',
        'profesor_id',
        'ciclo_escolar_id',
        'dia_id',
        'hora_id',
        'ubicacion',
        'conflicto_forzado',
        'forzado_por',
        'motivo_conflicto',
    ];

    protected $casts = [
        'taller_id' => 'integer',
        'profesor_id' => 'integer',
        'ciclo_escolar_id' => 'integer',
        'dia_id' => 'integer',
        'hora_id' => 'integer',
        'conflicto_forzado' => 'boolean',
        'forzado_por' => 'integer',
    ];

    public function taller()
    {
        return $this->belongsTo(Taller::class);
    }

    public function profesor()
    {
        return $this->belongsTo(Persona::class, 'profesor_id');
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(cicloEscolar::class, 'ciclo_escolar_id');
    }

    public function dia()
    {
        return $this->belongsTo(Dia::class);
    }

    public function hora()
    {
        return $this->belongsTo(Hora::class);
    }

    public function grupos()
    {
        return $this->belongsToMany(
            Grupo::class,
            'taller_sesion_grupo',
            'taller_sesion_id',
            'grupo_id'
        )->withTimestamps();
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class, 'taller_sesion_id');
    }

    public function usuarioForzado()
    {
        return $this->belongsTo(User::class, 'forzado_por');
    }
}
