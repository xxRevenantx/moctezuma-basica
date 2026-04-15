<?php

namespace App\Models;

use App\Observers\DiaObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(DiaObserver::class)]
class Dia extends Model
{

    use HasFactory;

    protected $table = 'dias';

    protected $fillable = [
        'nivel_id',
        'dia',
        'orden',
    ];

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class);
    }
}
