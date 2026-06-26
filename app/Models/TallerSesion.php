<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TallerSesion extends Model
{
    use HasFactory;

    public const ESTADO_BORRADOR = 'borrador';
    public const ESTADO_ACTIVA = 'activa';
    public const ESTADO_CERRADA = 'cerrada';
    public const ESTADO_ARCHIVADA = 'archivada';

    protected $table = 'taller_sesiones';

    protected $fillable = [
        'taller_id',
        'profesor_id',
        'ciclo_escolar_id',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'confirmada_at',
        'confirmada_por',
        'dia_id',
        'hora_id',
        'ubicacion',
        'conflicto_forzado',
        'forzado_por',
        'motivo_conflicto',
    ];

    protected $casts = [
        'taller_id' => 'integer',
        'profesor_id' => 'integer',
        'ciclo_escolar_id' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'confirmada_at' => 'datetime',
        'confirmada_por' => 'integer',
        'dia_id' => 'integer',
        'hora_id' => 'integer',
        'conflicto_forzado' => 'boolean',
        'forzado_por' => 'integer',
    ];

    public function taller()
    {
        return $this->belongsTo(Taller::class);
    }

    public function profesor()
    {
        return $this->belongsTo(Persona::class, 'profesor_id');
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(cicloEscolar::class, 'ciclo_escolar_id');
    }

    public function dia()
    {
        return $this->belongsTo(Dia::class);
    }

    public function hora()
    {
        return $this->belongsTo(Hora::class);
    }

    public function grupos()
    {
        return $this->belongsToMany(
            Grupo::class,
            'taller_sesion_grupo',
            'taller_sesion_id',
            'grupo_id'
        )->withTimestamps();
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class, 'taller_sesion_id');
    }

    public function usuarioForzado()
    {
        return $this->belongsTo(User::class, 'forzado_por');
    }

    public function usuarioConfirmacion()
    {
        return $this->belongsTo(User::class, 'confirmada_por');
    }

    public function scopeDelCiclo(Builder $query, int|string|null $cicloEscolarId): Builder
    {
        return $query->when(filled($cicloEscolarId), fn (Builder $q) => $q->where('ciclo_escolar_id', $cicloEscolarId));
    }

    public function scopeVisibles(Builder $query): Builder
    {
        return $query->where('estado', '!=', self::ESTADO_ARCHIVADA);
    }
}
