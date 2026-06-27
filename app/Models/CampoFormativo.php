<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampoFormativo extends Model
{
    protected $table = 'campos_formativos';

    protected $fillable = [
        'nombre',
        'slug',
        'color_fondo',
        'color_texto',
        'orden',
        'activo',
    ];

    protected $casts = [
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    public function materias()
    {
        return $this->hasMany(Materia::class, 'campo_formativo_id');
    }

    public function calificacionesOficiales()
    {
        return $this->hasMany(CalificacionCampoFormativo::class, 'campo_formativo_id');
    }
}
