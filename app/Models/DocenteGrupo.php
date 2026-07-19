<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocenteGrupo extends Model
{
    use HasFactory;

    protected $table = 'docente_grupos';

    protected $fillable = [
        'persona_id',
        'grupo_id',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }
}
