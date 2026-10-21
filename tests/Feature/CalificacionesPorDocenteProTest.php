<?php

use App\Exports\CalificacionesDocenteHoja;
use App\Livewire\Academico\CalificacionesPorDocente;

it('registra una ruta separada para el modulo administrativo de los tres niveles', function () {
    $rutas = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($route) => $route->getName() === 'misrutas.calificaciones-docente');

    expect($rutas)->toHaveCount(1);
    expect($rutas->first()->uri())->toBe('calificaciones-por-docente/{slug_nivel}');
    expect($rutas->first()->gatherMiddleware())->toContain('admin');
});

it('rechaza visitantes no autenticados en cada nivel', function () {
    foreach (['primaria', 'secundaria', 'bachillerato'] as $nivel) {
        $this->get(route('misrutas.calificaciones-docente', ['slug_nivel' => $nivel]))
            ->assertRedirect(route('login'));
    }
});

it('valida las claves especiales y el rango y mantiene el truncamiento de bachillerato', function () {
    $componente = new CalificacionesPorDocente();
    $metodo = new ReflectionMethod($componente, 'normalizar');

    $componente->slug_nivel = 'primaria';
    expect($metodo->invoke($componente, '9.5'))->toBe('9.5');
    expect($metodo->invoke($componente, 'np'))->toBe('NP');
    expect($metodo->invoke($componente, ''))->toBeNull();
    expect($metodo->invoke($componente, '11'))->toBe('INVALIDO');
    expect($metodo->invoke($componente, '=cmd()'))->toBe('INVALIDO');

    $componente->slug_nivel = 'bachillerato';
    expect($metodo->invoke($componente, '9.9'))->toBe('9');
});

it('produce encabezados de Excel con identificadores y columna nueva separada', function () {
    $hoja = new CalificacionesDocenteHoja('Matemáticas 1/A', [[42, 3, 'M0001', 'Alumno de prueba', '8', '']]);
    expect($hoja->array()[0])->toBe(['ASIGNACION_ID', 'PERIODO_ID', 'MATRICULA', 'ALUMNO', 'ACTUAL', 'NUEVA']);
    expect($hoja->title())->not->toContain('/');
    expect($hoja->array()[1][5])->toBe('');
});
