<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioDocenteDisponibilidad extends Model
{
    protected $table = 'horario_docente_disponibilidades';

    public const ESTADOS = ['preferido', 'disponible', 'autorizacion', 'no_disponible'];

    protected $fillable = ['configuracion_id', 'dia_id', 'hora_id', 'estado', 'motivo'];

    public function configuracion() { return $this->belongsTo(HorarioDocenteConfiguracion::class, 'configuracion_id'); }
    public function dia() { return $this->belongsTo(Dia::class); }
    public function hora() { return $this->belongsTo(Hora::class); }
}
