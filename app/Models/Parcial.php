<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parcial extends Model
{
    protected $table = 'parciales';
    protected $fillable = [
        'parcial',
        'descripcion',
    ];


    // RELACIONES CON PERIODOS
    public function periodos()
    {
        return $this->hasMany(Periodos::class, 'parcial_id');
    }
}
