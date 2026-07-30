<?php

use App\Models\Inscripcion;

test('solo el estatus activo con bandera activa es visible en listas operativas', function (string $estatus, bool $activo, bool $esperado): void {
    $alumno = new Inscripcion([
        'estatus' => $estatus,
        'activo' => $activo,
    ]);

    expect($alumno->visibleEnListas())->toBe($esperado);
})->with([
    'activo vigente' => ['activo', true, true],
    'activo con bandera apagada' => ['activo', false, false],
    'reingreso' => ['reingreso', true, false],
    'no promovido' => ['no_promovido', true, false],
    'pendiente reinscripcion' => ['pendiente_reinscripcion', true, false],
    'no reinscrito' => ['no_reinscrito', false, false],
    'baja temporal' => ['baja_temporal', false, false],
    'baja definitiva' => ['baja_definitiva', false, false],
    'trasladado' => ['trasladado', false, false],
    'egresado' => ['egresado', false, false],
    'inactivo' => ['inactivo', false, false],
]);

test('un alumno archivado no es visible aunque conserve estatus activo', function (): void {
    $alumno = new Inscripcion([
        'estatus' => 'activo',
        'activo' => true,
    ]);
    $alumno->deleted_at = now();

    expect($alumno->visibleEnListas())->toBeFalse();
});
