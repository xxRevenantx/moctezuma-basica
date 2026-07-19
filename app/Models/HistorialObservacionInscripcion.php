<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialObservacionInscripcion extends Model
{
    use HasFactory;

    protected $table = 'historial_observaciones_inscripciones';

    protected $fillable = [
        'observacion_inscripcion_id',
        'inscripcion_id',
        'ciclo_escolar_id',
        'usuario_id',
        'contenido_anterior',
        'contenido_nuevo',
        'origen',
    ];

    public function observacion()
    {
        return $this->belongsTo(ObservacionInscripcion::class, 'observacion_inscripcion_id');
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class);
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
