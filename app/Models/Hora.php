<?php

namespace App\Models;

use App\Observers\HoraObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;


#[ObservedBy(HoraObserver::class)]
class Hora extends Model
{
    protected $fillable = ['hora'];
}
