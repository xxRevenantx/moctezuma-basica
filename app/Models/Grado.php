<?php

namespace App\Models;

use App\Observers\GradoObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


#[ObservedBy(GradoObserver::class)]
class Grado extends Model
{
    /** @use HasFactory<\Database\Factories\GradoFactory> */
    use HasFactory;

    protected $fillable = [
        'nivel_id',
        'nombre',
        'orden'
    ];

    // RELACION CON NIVEL
    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }
}
