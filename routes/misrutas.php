<?php

use App\Http\Controllers\CicloEscolarController;
use App\Http\Controllers\DirectorController;
use App\Http\Controllers\EscuelaController;
use App\Http\Controllers\InscripcionController;
use App\Http\Controllers\NivelController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\SubmoduloNivelController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GradoController;
use App\Http\Controllers\GeneracionController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\PeriodosBachilleratoController;
use App\Http\Controllers\PeriodosBasicoController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\PersonaNivelController;
use App\Http\Controllers\SeleccionarGradoController;
use App\Http\Controllers\SeleccionarNivelController;
use App\Http\Controllers\SemestreController;
use App\Http\Controllers\TutorController;
use App\Models\PersonaNivel;


// RUTA INSCRIPCIÓN
Route::get('/inscripcion', [InscripcionController::class, 'inscripcion'])->name('misrutas.inscripcion');

// RUTA NIVELES
Route::get('/niveles', [NivelController::class, 'index'])->name('misrutas.niveles');


// RUTA CICLOS ESCOLARES
Route::get('/ciclos-escolares', [CicloEscolarController::class, 'index'])->name('misrutas.ciclos');

// RUTA TUTORES
Route::get('/tutores', [TutorController::class, 'index'])->name('misrutas.tutores');

// RUTA ESCUELA
Route::get('/escuela', [EscuelaController::class, 'index'])->name('misrutas.escuela');

// RUTA DIRECTIVOS
Route::get('/autoridades', [DirectorController::class, 'index'])->name('misrutas.autoridades');

// PERSONAL
Route::get('/personal', [PersonaController::class, 'index'])->name('misrutas.personal');

// ROLE DEL PERSONAL
Route::get('/roles-del-personal', [PersonaController::class, 'rolePersona'])->name('misrutas.role-persona');


// Plantilla
Route::get('/plantilla', [PersonaNivelController::class, 'plantilla'])->name('misrutas.plantilla');

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


// PDF
Route::get('/reanudaciones', [PDFController::class, 'reanudaciones'])->name('misrutas.reanudaciones');


Route::prefix('nivel')->group(function () {

    Route::get('{slug_nivel}', [SeleccionarNivelController::class, 'index'])->name('niveles.seleccionar-nivel');

    Route::get('{slug_nivel}/{accion}', [SubmoduloNivelController::class, 'submodulo'])->name('submodulos.accion');
});
