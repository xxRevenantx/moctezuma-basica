<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReanudacionLaboral extends Model
{
    use SoftDeletes;

    protected $table = 'reanudaciones_laborales';

    protected $fillable = [
        'lote_uuid',
        'persona_nivel_id',
        'persona_id',
        'nivel_id',
        'ciclo_escolar_id',
        'tipo_reanudacion',
        'fecha_director',
        'fecha_docente',
        'fecha_documento',
        'copias',
        'persona_nombre',
        'cargos',
        'es_directivo',
        'nivel_nombre',
        'nivel_slug',
        'ciclo_nombre',
        'grado_resumen',
        'grupo_resumen',
        'destinatario_nombre',
        'destinatario_cargo',
        'snapshot',
        'archivo_pdf_path',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'fecha_director' => 'date',
        'fecha_docente' => 'date',
        'fecha_documento' => 'date',
        'cargos' => 'array',
        'snapshot' => 'array',
        'es_directivo' => 'boolean',
    ];

    public function personaNivel()
    {
        return $this->belongsTo(PersonaNivel::class, 'persona_nivel_id');
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo_reanudacion) {
            'receso' => 'Receso escolar de agosto',
            'invierno' => 'Vacaciones de invierno',
            'primavera' => 'Vacaciones de primavera',
            default => ucfirst(str_replace('_', ' ', (string) $this->tipo_reanudacion)),
        };
    }
}
