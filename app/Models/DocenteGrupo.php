<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocenteGrupo extends Model
{
    use HasFactory;

    protected $table = 'docente_grupo';

    protected $fillable = [
        'persona_id',
        'grupo_id',
        'ciclo_escolar_id',
        'es_tutor',
    ];

    protected $casts = [
        'es_tutor' => 'boolean',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }
}
