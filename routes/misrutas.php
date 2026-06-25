<?php

use App\Http\Controllers\CicloEscolarController;
use App\Http\Controllers\DirectorController;
use App\Http\Controllers\DocumentosController;
use App\Http\Controllers\DocumentosPDFController;
use App\Http\Controllers\EscuelaController;
use App\Http\Controllers\ExpedienteDigitalController;
use App\Http\Controllers\ExpedientePersonalController;
use App\Http\Controllers\InscripcionController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\MatriculaHistorialPdfController;
use App\Http\Controllers\NivelController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\FichaController;
use App\Http\Controllers\SubmoduloNivelController;
use App\Http\Controllers\TodosHorariosProfesoresPdfController;
use App\Http\Controllers\WordController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GradoController;
use App\Http\Controllers\GeneracionController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\HorariosGeneralesPdfController;
use App\Http\Controllers\LugarPreescolarPDFController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\PeriodosBachilleratoController;
use App\Http\Controllers\PeriodosBasicoController;
use App\Http\Controllers\PeriodosController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\PersonaNivelController;
use App\Http\Controllers\ProfesorHorarioPdfController;
use App\Http\Controllers\ProfesorPdfController;
use App\Http\Controllers\RespaldoAcademicoController;
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

// RUTA ALUMNOS
Route::get('/alumnos', [InscripcionController::class, 'alumnos'])->name('misrutas.alumnos');


// RESPALDOS ACADÉMICOS
Route::get('/respaldos-academicos', [RespaldoAcademicoController::class, 'index'])
    ->middleware('admin')
    ->name('misrutas.respaldos-academicos');

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

// Documentación
Route::middleware('admin')->group(function () {
    Route::get('/expedientes-digitales', [ExpedienteDigitalController::class, 'index'])
        ->name('misrutas.expedientes');

    Route::get('/expedientes-digitales/alumno/{inscripcion}', [ExpedienteDigitalController::class, 'show'])
        ->withTrashed()
        ->name('misrutas.expedientes.show');

    Route::get('/expedientes-digitales/documento/{documento}/ver', [ExpedienteDigitalController::class, 'preview'])
        ->name('misrutas.expedientes.preview');

    Route::get('/expedientes-digitales/documento/{documento}/descargar', [ExpedienteDigitalController::class, 'download'])
        ->name('misrutas.expedientes.download');

    Route::get('/expedientes-digitales/alumno/{inscripcion}/zip', [ExpedienteDigitalController::class, 'zip'])
        ->withTrashed()
        ->name('misrutas.expedientes.zip');
});

// Expedientes del personal
Route::middleware('admin')->group(function () {
    Route::get('/expedientes-personal', [ExpedientePersonalController::class, 'index'])
        ->name('misrutas.expedientes-personal');

    Route::get('/expedientes-personal/persona/{persona}', [ExpedientePersonalController::class, 'show'])
        ->name('misrutas.expedientes-personal.show');

    Route::get('/expedientes-personal/documento/{documento}/ver', [ExpedientePersonalController::class, 'preview'])
        ->name('misrutas.expedientes-personal.preview');

    Route::get('/expedientes-personal/documento/{documento}/descargar', [ExpedientePersonalController::class, 'download'])
        ->name('misrutas.expedientes-personal.download');

    Route::get('/expedientes-personal/persona/{persona}/zip', [ExpedientePersonalController::class, 'zip'])
        ->name('misrutas.expedientes-personal.zip');
});

Route::get('/constancias', [DocumentosController::class, 'constancias'])->middleware('admin')->name('misrutas.constancias');
Route::get('/oficios', [DocumentosController::class, 'oficios'])->middleware('admin')->name('misrutas.oficios');


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



Route::get('/nivel/{slug_nivel}/matricula-historica/pdf', MatriculaHistorialPdfController::class)
    ->middleware('admin')
    ->name('misrutas.matricula.historial.pdf');


Route::get('/promedios-generales/{slug_nivel}/boleta/{tipo}/pdf', [PDFController::class, 'boletareconocimientoPromedioPdf'])
    ->name('misrutas.promedios.boleta.pdf');


// FICHAS DESCRIPTIVAS PREESCOLAR
Route::get('/fichas/preescolar/excel', [FichaController::class, 'excel'])
    ->name('misrutas.fichas.excel');

Route::get('/fichas/preescolar/grupo/pdf', [FichaController::class, 'grupoPdf'])
    ->name('misrutas.fichas.grupo.pdf');

Route::get('/fichas/preescolar/alumno/{inscripcion}/pdf', [FichaController::class, 'alumnoPdf'])
    ->name('misrutas.fichas.alumno.pdf');

Route::get('/lugares-preescolar/{lugarPreescolar}/pdf', [LugarPreescolarPDFController::class, 'show'])
    ->name('misrutas.lugares-preescolar.pdf');

Route::get('/lugares-preescolar/{lugarPreescolar}/diploma', [LugarPreescolarPDFController::class, 'diploma'])
    ->name('misrutas.lugares-preescolar.diploma');

// PDF
Route::get('/reanudaciones', [PDFController::class, 'reanudaciones'])->name('misrutas.reanudaciones');

Route::get('/horarios/pdf', [PDFController::class, 'horario_pdf'])->name('misrutas.horarios.pdf');

Route::get('/generales/horarios/pdf', HorariosGeneralesPdfController::class)
    ->name('generales.horarios.pdf');

Route::get('/profesores/horario-profesor/pdf', ProfesorHorarioPdfController::class)
    ->name('profesor.horario.pdf');

Route::get('/profesores/horarios/todos/pdf', TodosHorariosProfesoresPdfController::class)
    ->name('profesores.horarios.todos.pdf');

Route::get('/listas/pdf/{slug_nivel}', [PDFController::class, 'lista_pdf'])
    ->name('accion.generales.listas.pdf');


Route::get('/calificaciones/pdf', [PDFController::class, 'calificaciones_pdf'])->name('misrutas.calificaciones.pdf');

Route::get('/calificaciones/boleta', [PDFController::class, 'boleta_calificaciones_pdf'])
    ->name('misrutas.boleta.calificaciones.pdf');

Route::get('/calificaciones/diploma', [PDFController::class, 'diploma_pdf'])
    ->name('misrutas.diploma.pdf');



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

Route::get('/constancias/descargar/zip', [DocumentosPDFController::class, 'constanciasZip'])
    ->middleware('admin')
    ->name('misrutas.constancias.zip');

Route::get('/constancias/{constancia}/pdf', [DocumentosPDFController::class, 'constanciaPdf'])
    ->middleware('admin')
    ->name('misrutas.constancias.pdf');


Route::get('/oficios/{oficio}/pdf', [DocumentosPDFController::class, 'oficioPdf'])
    ->middleware('admin')
    ->name('misrutas.oficios.pdf');


Route::prefix('nivel')->group(function () {

    Route::get('{slug_nivel}', [SeleccionarNivelController::class, 'index'])->name('niveles.seleccionar-nivel');

    Route::get('{slug_nivel}/{accion}', [SubmoduloNivelController::class, 'submodulo'])->name('submodulos.accion');
});
