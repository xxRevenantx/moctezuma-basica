<?php

it('mantiene la restricción de exportación masiva solo para administración', function () {
    $vista = file_get_contents(resource_path('views/livewire/accion/calificacion.blade.php'));
    $rutas = file_get_contents(base_path('routes/misrutas.php'));
    $componente = file_get_contents(app_path('Livewire/Accion/Concerns/GestionaContextoCalificaciones.php'));

    expect($vista)
        ->toContain('@php($esProfesor = (bool) auth()->user()?->isProfessor())')
        ->toContain('@unless ($esProfesor)')
        ->toContain('misrutas.calificaciones.documentos.masivos')
        ->toContain('wire:loading.delay.short.flex wire:target="{{ $objetivosTabla }}"');

    expect($rutas)->toContain("->middleware('admin')");
    expect($componente)->toContain('public function getEsProfesorAutenticadoProperty(): bool');
});
