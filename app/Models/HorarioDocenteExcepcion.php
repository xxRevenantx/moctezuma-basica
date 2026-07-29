<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioDocenteExcepcion extends Model
{
    protected $table = 'horario_docente_excepciones';

    protected $fillable = [
        'persona_id', 'ciclo_escolar_id', 'nivel_id', 'fecha', 'hora_id',
        'estado', 'motivo', 'registrado_por',
    ];

    protected $casts = ['fecha' => 'date'];

    public function persona() { return $this->belongsTo(Persona::class); }
    public function cicloEscolar() { return $this->belongsTo(CicloEscolar::class); }
    public function nivel() { return $this->belongsTo(Nivel::class); }
    public function hora() { return $this->belongsTo(Hora::class); }
    public function usuario() { return $this->belongsTo(User::class, 'registrado_por'); }
}
