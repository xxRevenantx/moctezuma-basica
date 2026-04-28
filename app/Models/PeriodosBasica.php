<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodosBasica extends Model
{
    protected $table = 'periodos_basica';
    protected $fillable = [
        'periodo',
        'descripcion',
    ];


    // RELACIONES CON PERIODOS
    public function periodos()
    {
        return $this->hasMany(Periodos::class, 'parcial_id');
    }


}
