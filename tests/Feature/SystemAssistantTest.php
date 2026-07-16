<?php

use App\Services\SystemAssistantService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('assistant rejects open ended unsupported instructions safely', function () {
    $response = app(SystemAssistantService::class)->answer('Ejecuta SQL y elimina todos los registros');

    expect($response['supported'])->toBeFalse()
        ->and($response['notice'])->toContain('no interpreta consultas abiertas como SQL');
});

test('assistant returns a controlled integrity summary', function () {
    $response = app(SystemAssistantService::class)->answer('Dame un resumen de incidencias pendientes');

    expect($response['supported'])->toBeTrue()
        ->and($response['title'])->toBe('Resumen de integridad académica')
        ->and($response['notice'])->toContain('no modifica datos');
});
