<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class cicloEscolar extends Model
{
    /** @use HasFactory<\Database\Factories\CicloEscolarFactory> */
    protected $table = 'ciclo_escolares';
    use HasFactory;
}
