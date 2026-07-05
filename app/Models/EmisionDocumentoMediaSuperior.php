<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmisionDocumentoMediaSuperior extends Model
{
    protected $table = 'emisiones_documentos_media_superior';

    protected $fillable = [
        'nivel_id',
        'inscripcion_id',
        'tipo',
        'clave_contexto',
        'folio',
        'formato',
        'contexto',
        'hash_datos',
        'emitido_por',
        'emitido_at',
    ];

    protected $casts = [
        'contexto' => 'array',
        'emitido_at' => 'datetime',
    ];

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class)->withTrashed();
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'emitido_por');
    }
}
