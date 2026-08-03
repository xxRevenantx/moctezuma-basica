<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReasignacionDocenteLote extends Model
{
    public const ESTADO_APLICADA = 'aplicada';
    public const ESTADO_REVERTIDA = 'revertida';
    public const ESTADO_REVERSION_PARCIAL = 'reversion_parcial';

    protected $table = 'reasignacion_docente_lotes';

    protected $fillable = [
        'uuid',
        'ciclo_escolar_id',
        'nivel_id',
        'profesor_origen_id',
        'profesor_destino_id',
        'modo',
        'estado',
        'total_asignaciones',
        'total_horarios',
        'total_versiones',
        'total_conflictos',
        'conflictos_autorizados',
        'motivo_autorizacion_conflictos',
        'metadata',
        'aplicado_por',
        'aplicado_at',
        'revertido_por',
        'revertido_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'conflictos_autorizados' => 'boolean',
        'total_asignaciones' => 'integer',
        'total_horarios' => 'integer',
        'total_versiones' => 'integer',
        'total_conflictos' => 'integer',
        'aplicado_at' => 'datetime',
        'revertido_at' => 'datetime',
    ];

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function profesorOrigen()
    {
        return $this->belongsTo(Persona::class, 'profesor_origen_id');
    }

    public function profesorDestino()
    {
        return $this->belongsTo(Persona::class, 'profesor_destino_id');
    }

    public function usuarioAplicacion()
    {
        return $this->belongsTo(User::class, 'aplicado_por');
    }

    public function usuarioReversion()
    {
        return $this->belongsTo(User::class, 'revertido_por');
    }

    public function detalles()
    {
        return $this->hasMany(ReasignacionDocenteDetalle::class, 'reasignacion_docente_lote_id');
    }

    public function puedeRevertirse(): bool
    {
        return $this->estado === self::ESTADO_APLICADA;
    }
}
