<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionMediaSuperior extends Model
{
    protected $table = 'configuraciones_media_superior';

    protected $fillable = [
        'nivel_id',
        'nombre_plantel_oficial',
        'numero_acuerdo',
        'modalidad',
        'turno',
        'localidad_expedicion',
        'logo_seg_path',
        'logo_plantel_path',
        'mostrar_materias_extra',
    ];

    protected $casts = [
        'mostrar_materias_extra' => 'boolean',
    ];

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }
}
