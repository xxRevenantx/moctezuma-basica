<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MesesBasica extends Model
{

    protected $table = "meses_basica";

    protected $fillable = [
        'meses',
        'meses_corto',
    ];


    // RELACIONES CON PERIODOS
    public function periodos()
    {
        return $this->hasMany(Periodos::class, 'mes_basica_id');
    }



}
