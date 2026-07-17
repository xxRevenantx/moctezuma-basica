<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObservacionInscripcion extends Model
{
    use HasFactory;

    protected $table = 'observaciones_inscripciones';

    protected $fillable = [
        'inscripcion_id',
        'ciclo_escolar_id',
        'contenido',
        'creado_por',
        'actualizado_por',
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class);
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(cicloEscolar::class, 'ciclo_escolar_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function historial()
    {
        return $this->hasMany(HistorialObservacionInscripcion::class, 'observacion_inscripcion_id')
            ->latest('created_at');
    }
}
