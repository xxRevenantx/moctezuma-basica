<?php

namespace App\Models;

use App\Observers\HoraObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


#[ObservedBy(HoraObserver::class)]
class Hora extends Model
{
    use HasFactory;

    protected $table = 'horas';

    protected $fillable = [
        'nivel_id',
        'hora_inicio',
        'hora_fin',
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
