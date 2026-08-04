<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventoDocumentoTutor extends Model
{
    use HasFactory;

    protected $table = 'eventos_documentos_tutores';

    protected $fillable = [
        'tutor_id',
        'documento_tutor_id',
        'organizacion_id',
        'accion',
        'descripcion',
        'datos_anteriores',
        'datos_nuevos',
        'usuario_id',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'datos_anteriores' => 'array',
            'datos_nuevos' => 'array',
        ];
    }

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }

    public function documento()
    {
        return $this->belongsTo(DocumentoTutor::class, 'documento_tutor_id');
    }

    public function organizacion()
    {
        return $this->belongsTo(OrganizacionDocumentoTutor::class, 'organizacion_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
