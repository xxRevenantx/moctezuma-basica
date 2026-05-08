<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsignacionGrupo extends Model
{
    protected $fillable = [
        'nombre',
    ];

    public function grupos()
    {
        return $this->hasMany(Grupo::class);
    }
}
