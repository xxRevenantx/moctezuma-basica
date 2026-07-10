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
        'fecha_acuerdo',
        'modalidad',
        'turno',
        'calificacion_minima',
        'calificacion_maxima',
        'minima_aprobatoria',
        'localidad_expedicion',
        'logo_seg_path',
        'logo_plantel_path',
        'texto_certificado',
        'leyenda_certificado',
        'mostrar_materias_extra',
        'mostrar_foto_historial',
    ];

    protected $casts = [
        'fecha_acuerdo' => 'date',
        'calificacion_minima' => 'decimal:2',
        'calificacion_maxima' => 'decimal:2',
        'minima_aprobatoria' => 'decimal:2',
        'mostrar_materias_extra' => 'boolean',
        'mostrar_foto_historial' => 'boolean',
    ];

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }
}
