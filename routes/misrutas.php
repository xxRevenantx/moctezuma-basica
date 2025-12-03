<?php

use App\Http\Controllers\DirectorController;
use App\Http\Controllers\EscuelaController;
use App\Http\Controllers\NivelController;
use Illuminate\Support\Facades\Route;



// RUTA NIVELES
Route::get('/niveles', [NivelController::class, 'index'])->name('misrutas.niveles');

// RUTA ESCUELA
Route::get('/escuela', [EscuelaController::class, 'index'])->name('misrutas.escuela');

// RUTA DIRECTIVOS
Route::get('/directivos', [DirectorController::class, 'index'])->name('misrutas.directivos');
