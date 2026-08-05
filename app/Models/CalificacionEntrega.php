<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalificacionEntrega extends Model
{
    protected $fillable = [
        'folio',
        'user_id',
        'persona_id',
        'periodo_id',
        'ciclo_escolar_id',
        'nivel_id',
        'generacion_id',
        'grado_id',
        'grupo_id',
        'semestre_id',
        'version',
        'estado',
        'docente_nombre',
        'docente_curp',
        'correo_institucional',
        'declaracion',
        'ip_confirmacion',
        'user_agent',
        'confirmada_at',
        'totales',
        'snapshot_sha256',
        'pdf_disk',
        'pdf_path',
        'pdf_sha256',
        'reabierta_por',
        'reabierta_at',
        'motivo_reapertura',
    ];

    protected $casts = [
        'semestre_id' => 'integer',
        'version' => 'integer',
        'totales' => 'array',
        'confirmada_at' => 'datetime',
        'reabierta_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function detalles()
    {
        return $this->hasMany(CalificacionEntregaDetalle::class);
    }

    public function periodo()
    {
        return $this->belongsTo(Periodos::class, 'periodo_id');
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class);
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function generacion()
    {
        return $this->belongsTo(Generacion::class);
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class);
    }
}
