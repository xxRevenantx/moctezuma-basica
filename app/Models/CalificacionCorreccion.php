<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalificacionCorreccion extends Model
{
    public const SOLICITADA = 'solicitada';
    public const AUTORIZADA = 'autorizada';
    public const APLICADA = 'aplicada';
    public const RECHAZADA = 'rechazada';

    protected $table = 'calificacion_correcciones';

    protected $fillable = [
        'calificacion_id', 'periodo_id', 'inscripcion_id', 'estado', 'motivo',
        'valor_anterior', 'valor_propuesto', 'solicitada_por', 'solicitada_at',
        'autorizada_por', 'autorizada_at', 'observacion_autorizacion',
        'aplicada_por', 'aplicada_at',
    ];

    protected $casts = [
        'valor_anterior' => 'array',
        'valor_propuesto' => 'array',
        'solicitada_at' => 'datetime',
        'autorizada_at' => 'datetime',
        'aplicada_at' => 'datetime',
    ];

    public function calificacion() { return $this->belongsTo(Calificacion::class); }
    public function periodo() { return $this->belongsTo(Periodos::class, 'periodo_id'); }
    public function inscripcion() { return $this->belongsTo(Inscripcion::class); }
    public function solicitante() { return $this->belongsTo(User::class, 'solicitada_por'); }
    public function autorizador() { return $this->belongsTo(User::class, 'autorizada_por'); }
}
