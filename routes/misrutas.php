<?php

use App\Http\Controllers\CicloEscolarController;
use App\Http\Controllers\DirectorController;
use App\Http\Controllers\EscuelaController;
use App\Http\Controllers\NivelController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GradoController;
use App\Http\Controllers\GeneracionController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\PeriodosBachilleratoController;
use App\Http\Controllers\PeriodosBasicoController;
use App\Http\Controllers\SemestreController;


// RUTA NIVELES
Route::get('/niveles', [NivelController::class, 'index'])->name('misrutas.niveles');


// RUTA CICLOS ESCOLARES
Route::get('/ciclos-escolares', [CicloEscolarController::class, 'index'])->name('misrutas.ciclos');

// RUTA ESCUELA
Route::get('/escuela', [EscuelaController::class, 'index'])->name('misrutas.escuela');

// RUTA DIRECTIVOS
Route::get('/directivos', [DirectorController::class, 'index'])->name('misrutas.directivos');

// RUTAS DE GRADOS
Route::get('/grados', [GradoController::class, 'index'])->name('misrutas.grados');
// RUTAS DE GENERACIONES
Route::get('/generaciones', [GeneracionController::class, 'index'])->name('misrutas.generaciones');

//RUTAS DE SEMESTRES
Route::get('/semestres', [SemestreController::class, 'index'])->name('misrutas.semestres');


// RUTAS DE GRUPOS
Route::get('/grupos', [GrupoController::class, 'index'])->name('misrutas.grupos');


// RUTAS DE PERIODOS BASICA
Route::get('/periodos-basica', [PeriodosBasicoController::class, 'index'])->name('misrutas.periodos-basica');

// RUTAS DE PERIODOS BACHILLERATO
Route::get('/periodos-bachillerato', [PeriodosBachilleratoController::class, 'index'])->name('misrutas.periodos-bachillerato');
