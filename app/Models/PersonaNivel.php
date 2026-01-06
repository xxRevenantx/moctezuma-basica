<?php

namespace App\Models;

use App\Observers\PersonaNivelObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(PersonaNivelObserver::class)]
class PersonaNivel extends Model
{
    use HasFactory;

    protected $table = 'persona_nivel';

    protected $fillable = [
        'persona_id',
        'nivel_id',
        'ingreso_seg',
        'ingreso_sep',
        'ingreso_ct',
        'orden',
    ];

    // =========================
    // Relaciones base
    // =========================

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function detalles()
    {
        return $this->hasMany(PersonaNivelDetalle::class, 'persona_nivel_id');
    }



}
