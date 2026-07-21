<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InscripcionCicloAsignacion extends Model
{
    protected $table = 'inscripcion_ciclo_asignaciones';

    protected $guarded = [];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'es_actual' => 'boolean',
        'snapshot' => 'array',
    ];

    public function inscripcionCiclo() { return $this->belongsTo(InscripcionCiclo::class); }
    public function nivel() { return $this->belongsTo(Nivel::class); }
    public function grado() { return $this->belongsTo(Grado::class); }
    public function generacion() { return $this->belongsTo(Generacion::class); }
    public function grupo() { return $this->belongsTo(Grupo::class); }
    public function semestre() { return $this->belongsTo(Semestre::class); }
    public function usuarioRegistro() { return $this->belongsTo(User::class, 'registrado_por'); }
}
