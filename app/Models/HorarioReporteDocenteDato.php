<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioReporteDocenteDato extends Model
{
    protected $table = 'horario_reporte_docente_datos';

    protected $fillable = [
        'nivel_id',
        'ciclo_escolar_id',
        'persona_id',
        'nombramiento',
        'clave_presupuestal',
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

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function usuarioActualizacion()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }
}
