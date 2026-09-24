<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioReporteConfiguracion extends Model
{
    protected $table = 'horario_reporte_configuraciones';

    protected $fillable = [
        'nivel_id',
        'ciclo_escolar_id',
        'escuela',
        'cct',
        'zona_escolar',
        'turno',
        'director',
        'supervisor',
        'actualizado_por',
    ];

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class);
    }

    public function usuarioActualizacion()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }
}
