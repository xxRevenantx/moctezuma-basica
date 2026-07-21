<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantillaPersonalNivel extends Model
{
    public const ESTADO_BORRADOR = 'borrador';
    public const ESTADO_REVISION = 'en_revision';
    public const ESTADO_PUBLICADA = 'publicada';
    public const ESTADO_CERRADA = 'cerrada';

    protected $table = 'plantillas_personal_nivel';

    protected $fillable = [
        'ciclo_escolar_id',
        'nivel_id',
        'estado',
        'copiada_de_id',
        'publicada_at',
        'publicada_por',
        'cerrada_at',
        'cerrada_por',
        'reabierta_at',
        'reabierta_por',
        'motivo_reapertura',
        'diagnostico',
        'observaciones',
    ];

    protected $casts = [
        'publicada_at' => 'datetime',
        'cerrada_at' => 'datetime',
        'reabierta_at' => 'datetime',
        'diagnostico' => 'array',
    ];

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function membresias()
    {
        return $this->hasMany(PersonaNivelCiclo::class, 'plantilla_personal_nivel_id');
    }

    public function plantillaOrigen()
    {
        return $this->belongsTo(self::class, 'copiada_de_id');
    }

    public function usuarioPublico()
    {
        return $this->belongsTo(User::class, 'publicada_por');
    }

    public function usuarioCerro()
    {
        return $this->belongsTo(User::class, 'cerrada_por');
    }

    public function usuarioReabrio()
    {
        return $this->belongsTo(User::class, 'reabierta_por');
    }

    public function esEditable(): bool
    {
        return $this->estado !== self::ESTADO_CERRADA;
    }

    public function disponibleParaDocumentos(): bool
    {
        return in_array($this->estado, [self::ESTADO_PUBLICADA, self::ESTADO_CERRADA], true);
    }

    public function getEtiquetaEstadoAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_BORRADOR => 'Borrador',
            self::ESTADO_REVISION => 'En revisión',
            self::ESTADO_PUBLICADA => 'Publicada',
            self::ESTADO_CERRADA => 'Cerrada',
            default => ucfirst((string) $this->estado),
        };
    }
}
