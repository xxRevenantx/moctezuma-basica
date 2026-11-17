<?php

it('mantiene la definición inline de profesor y las opciones de exportación sin mezclar bloques Blade PHP', function () {
    $vista = file_get_contents(resource_path('views/livewire/accion/calificacion.blade.php'));

    expect($vista)
        ->toStartWith('@php($esProfesor = (bool) auth()->user()?->isProfessor())')
        ->toContain('@unless ($esProfesor)')
        ->toContain('misrutas.calificaciones.documentos.masivos')
        ->toContain('wire:loading.delay.short.flex wire:target="{{ $objetivosTabla }}"')
        ->toContain('wire:click="descargarInformeIaPdf"')
        ->toContain('wire:click="descargarInformeIaWord"');
});
