<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartaCompromisoConfiguracion extends Model
{
    protected $table = 'carta_compromiso_configuraciones';

    protected $fillable = [
        'nivel_id',
        'membrete_tipo',
        'encabezado_linea_1',
        'encabezado_linea_2',
        'lugar_default',
        'leyenda_anual',
        'destinatario_institucion',
        'created_by',
        'updated_by',
    ];

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }
}
