<?php

use App\Http\Controllers\CicloEscolarController;
use App\Http\Controllers\DirectorController;
use App\Http\Controllers\EscuelaController;
use App\Http\Controllers\InscripcionController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\NivelController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\SubmoduloNivelController;
use App\Http\Controllers\WordController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GradoController;
use App\Http\Controllers\GeneracionController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\PeriodosBachilleratoController;
use App\Http\Controllers\PeriodosBasicoController;
use App\Http\Controllers\PeriodosController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\PersonaNivelController;
use App\Http\Controllers\ProfesorPdfController;
use App\Http\Controllers\SeleccionarGradoController;
use App\Http\Controllers\SeleccionarNivelController;
use App\Http\Controllers\SemestreController;
use App\Http\Controllers\TutorController;
use App\Models\PersonaNivel;
use App\Models\Profesor;

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

// Ruta profesores
Route::get('/profesores', [PersonaNivelController::class, 'profesores'])->name('misrutas.profesores');

// RUTAS DE GRADOS
Route::get('/grados', [GradoController::class, 'index'])->name('misrutas.grados');
// RUTAS DE GENERACIONES
Route::get('/generaciones', [GeneracionController::class, 'index'])->name('misrutas.generaciones');

//RUTAS DE SEMESTRES
Route::get('/semestres', [SemestreController::class, 'index'])->name('misrutas.semestres');


// RUTAS DE GRUPOS
Route::get('/grupos', [GrupoController::class, 'index'])->name('misrutas.grupos');


// RUTAS DE PERIODOS
Route::get('/periodos', [PeriodosController::class, 'index'])->name('misrutas.periodos');

//RUTA MATERIAS
Route::get('/materias', [MateriaController::class, 'index'])->name('misrutas.materias');


Route::get('/nivel/{slug_nivel}/matricula/{inscripcion}/editar', [MatriculaController::class, 'editar'])
    ->name('misrutas.matricula.editar');


Route::get('/promedios-generales/{slug_nivel}/boleta/{tipo}/pdf', [PDFController::class, 'boletaPromedioPdf'])
    ->name('misrutas.promedios.boleta.pdf');

// PDF
Route::get('/reanudaciones', [PDFController::class, 'reanudaciones'])->name('misrutas.reanudaciones');

Route::get('/horarios/pdf', [PDFController::class, 'horario_pdf'])->name('misrutas.horarios.pdf');

Route::get('/nivel/{slug_nivel}/listas/pdf', [PDFController::class, 'lista_pdf'])->name('accion.generales.listas.pdf');

Route::get('/calificaciones/pdf', [PDFController::class, 'calificaciones_pdf'])->name('misrutas.calificaciones.pdf');

Route::get('/calificaciones/boleta', [PDFController::class, 'boleta_calificaciones_pdf'])
    ->name('misrutas.boleta.calificaciones.pdf');



// Ruta nueva para reconocimiento
Route::get('/reconocimiento/calificaciones/pdf', [PDFController::class, 'reconocimiento_calificaciones_pdf'])
    ->name('misrutas.reconocimiento.calificaciones.pdf');

// Opcional: conserva la ruta anterior si ya existen enlaces guardados o código viejo.
// Route::get('/diploma/calificaciones/pdf', [PDFController::class, 'reconocimiento_calificaciones_pdf'])
//     ->name('misrutas.diploma.calificaciones.pdf');



Route::get('/credenciales/profesores/pdf', [PDFController::class, 'credencial_profesor_pdf'])
    ->name('credenciales.profesores.pdf');


Route::get('/profesor/listas/asistencia/pdf', [ProfesorPdfController::class, 'asistencia'])
    ->name('profesor.listas.asistencia.pdf');

Route::get('/profesor/listas/evaluacion/pdf', [ProfesorPdfController::class, 'evaluacion'])
    ->name('profesor.listas.evaluacion.pdf');

// WORD
Route::get('/{slug_nivel}/listas/word', [WordController::class, 'lista_word'])
    ->name('lista.evaluacion.word');

Route::get('/generales/{slug_nivel}/credenciales/pdf', [PDFController::class, 'credenciales_pdf'])
    ->name('generales.credenciales.pdf');

Route::prefix('nivel')->group(function () {

    Route::get('{slug_nivel}', [SeleccionarNivelController::class, 'index'])->name('niveles.seleccionar-nivel');

    Route::get('{slug_nivel}/{accion}', [SubmoduloNivelController::class, 'submodulo'])->name('submodulos.accion');
});
