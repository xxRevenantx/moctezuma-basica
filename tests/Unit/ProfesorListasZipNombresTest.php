<?php

use App\Http\Controllers\ProfesorListasZipController;

it('normaliza nombres de carpetas del ZIP y elimina separadores de ruta', function () {
    $controller = new ProfesorListasZipController();
    $metodo = new ReflectionMethod($controller, 'nombreSeguro');

    expect($metodo->invoke($controller, 'Formación Cívica y Ética'))->toBe('Formacion_Civica_y_Etica')
        ->and($metodo->invoke($controller, '../../Otra\\Carpeta'))->toBe('Otra_Carpeta')
        ->and($metodo->invoke($controller, 'ÁÑ'))->toBe('AN');
});

it('limita el tamaño del componente de cada ruta del ZIP', function () {
    $controller = new ProfesorListasZipController();
    $metodo = new ReflectionMethod($controller, 'nombreSeguro');
    expect(strlen($metodo->invoke($controller, str_repeat('Materia con nombre largo ', 10))))->toBeLessThanOrEqual(70);
});
