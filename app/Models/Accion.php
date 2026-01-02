<?php

namespace App\Models;

use App\Observers\AccionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(AccionObserver::class)]
class Accion extends Model
{
    /** @use HasFactory<\Database\Factories\AccionFactory> */
    use HasFactory;
    protected $table = "acciones";

    protected $fillable = [
        'accion',
        'slug',
        'orden',
    ];


}
