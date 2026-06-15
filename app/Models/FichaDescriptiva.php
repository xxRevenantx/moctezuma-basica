<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FichaDescriptiva extends Model
{
    protected $table = 'ficha_descriptivas';

    protected $fillable = [
        'inscripcion_id',
        'nivel_id',
        'grado_id',
        'grupo_id',
        'generacion_id',
        'ciclo_escolar_id',
        'periodo',
        'campo',
        'descripcion',
        'capturado_por',
        'fecha_captura',
    ];

    protected $casts = [
        'periodo' => 'integer',
        'fecha_captura' => 'datetime',
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class);
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function generacion()
    {
        return $this->belongsTo(Generacion::class);
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(cicloEscolar::class, 'ciclo_escolar_id');
    }

    public function capturador()
    {
        return $this->belongsTo(User::class, 'capturado_por');
    }
}
