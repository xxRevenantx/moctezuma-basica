<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreinscripcionCiclo extends Model
{
    protected $table = 'preinscripciones_ciclos';

    protected $guarded = [];

    protected $casts = [
        'fecha_preinscripcion' => 'date',
        'formalizada_at' => 'datetime',
        'cancelada_at' => 'datetime',
        'snapshot' => 'array',
    ];

    public function inscripcion() { return $this->belongsTo(Inscripcion::class)->withTrashed(); }
    public function cicloEscolar() { return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id'); }
    public function nivel() { return $this->belongsTo(Nivel::class); }
    public function grado() { return $this->belongsTo(Grado::class); }
    public function generacion() { return $this->belongsTo(Generacion::class); }
    public function grupo() { return $this->belongsTo(Grupo::class); }
    public function semestre() { return $this->belongsTo(Semestre::class); }
    public function usuarioFormalizo() { return $this->belongsTo(User::class, 'formalizada_por'); }
    public function usuarioCancelo() { return $this->belongsTo(User::class, 'cancelada_por'); }
}
