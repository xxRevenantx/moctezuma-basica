<?php

namespace App\Models;

use App\Observers\DiaObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(DiaObserver::class)]
class Dia extends Model
{
    protected $fillable = ['dia'];
}
