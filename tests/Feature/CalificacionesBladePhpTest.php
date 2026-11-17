<?php

use Symfony\Component\Process\Process;

it('compila y valida PHP de la vista de calificaciones para detectar ParseError de Blade', function () {
    $vista = file_get_contents(resource_path('views/livewire/accion/calificacion.blade.php'));
    preg_match_all('/(?<!@)@php(.*?)@endphp/s', $vista, $bloques);
    expect($bloques[0])->toBe([]);

    $php = app('blade.compiler')->compileString($vista);
    $archivo = tempnam(sys_get_temp_dir(), 'blade_calif_');
    expect($archivo)->not->toBeFalse();

    try {
        file_put_contents($archivo, $php);
        $proceso = new Process([PHP_BINARY, '-l', $archivo]);
        $proceso->run();
        expect($proceso->isSuccessful())->toBeTrue($proceso->getErrorOutput());
    } finally {
        @unlink($archivo);
    }
});
