<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MesesBachillerato extends Model
{
    protected $table = "meses_bachilleratos";

    protected $fillable = [
        'meses',
        'meses_corto',
    ];

    //RELACION CON SEMESTRES
    public function semestres()
    {
        return $this->hasMany(Semestre::class, 'mes_id');
    }
}
