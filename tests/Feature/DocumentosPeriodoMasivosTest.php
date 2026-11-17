<?php

use Illuminate\Support\Facades\Route;

it('expone exportaciones masivas solo con autorización administrativa', function () {
    $ruta = Route::getRoutes()->getByName('misrutas.calificaciones.documentos.masivos');

    expect($ruta)->not->toBeNull();
    expect($ruta->methods())->toContain('GET');
    expect($ruta->gatherMiddleware())->toContain('admin');
});

it('reutiliza la lógica de promedios y lugares de documentos individuales', function () {
    $controlador = file_get_contents(app_path('Http/Controllers/DocumentosPeriodoMasivosController.php'));
    $individual = file_get_contents(app_path('Http/Controllers/PDFController.php'));

    expect($controlador)
        ->toContain('ListaAcademicaService::class')
        ->toContain('boleta_calificaciones_pdf($solicitud)')
        ->toContain('reconocimiento_calificaciones_pdf($solicitud)')
        ->toContain("[1, 2, 3]")
        ->toContain('descargarLote(')
        ->toContain('setSourceFile($archivo)');
    expect($individual)->toContain("documentos_periodo_masivos_datos");
});
