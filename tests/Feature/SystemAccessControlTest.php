<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the control center route is registered once and requires authentication', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($route) => $route->getName() === 'misrutas.centro-control');

    expect($routes)->toHaveCount(1);
    expect($routes->first()->uri())->toBe('centro-control');
    expect($routes->first()->gatherMiddleware())->toContain('auth');
});

test('guests cannot open the control center', function () {
    $this->get(route('misrutas.centro-control'))
        ->assertRedirect(route('login'));
});

test('administrator can open the control center', function () {
    $user = User::factory()->create([
        'is_admin' => true,
        'activo' => true,
    ]);

    $this->actingAs($user)
        ->get(route('misrutas.centro-control'))
        ->assertOk();
});

test('inactive non administrator cannot use role permissions', function () {
    $user = User::factory()->make([
        'is_admin' => false,
        'activo' => false,
        'rol_sistema' => 'control_escolar',
    ]);

    expect($user->canAccess('administracion.acceder'))->toBeFalse();
});

test('role permissions and explicit overrides are respected', function () {
    $user = User::factory()->make([
        'is_admin' => false,
        'activo' => true,
        'rol_sistema' => 'profesor',
        'permisos' => ['integridad.consultar', '!calificaciones.eliminar'],
    ]);

    expect($user->canAccess('integridad.consultar'))->toBeTrue()
        ->and($user->canAccess('calificaciones.consultar'))->toBeTrue()
        ->and($user->canAccess('calificaciones.eliminar'))->toBeFalse()
        ->and($user->canAccess('usuarios.gestionar'))->toBeFalse();
});
