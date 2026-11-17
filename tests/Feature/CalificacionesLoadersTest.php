it('usa un indicador por descarga masiva y conserva el loader no bloqueante de la matriz', function () {
    $vista = file_get_contents(resource_path('views/livewire/accion/calificacion.blade.php'));

    expect($vista)
        ->toContain("descargando: ''")
        ->toContain('async descargarMasivo(event)')
        ->toContain('misrutas.calificaciones.documentos.masivos')
        ->toContain('Generando el archivo con los datos del periodo')
        ->toContain('wire:loading.delay.short.flex wire:target="{{ $objetivosTabla }}"')
        ->not->toContain('wire:model.live="boleta_inscripcion_id"')
        ->not->toContain('wire:model.live="reconocimiento_inscripcion_id"')
        ->toContain('pointer-events-none absolute right-3 top-3');
});

it('conserva las descargas y evita el overlay global que bloqueaba la matriz', function () {
    $vista = file_get_contents(resource_path('views/livewire/accion/calificacion.blade.php'));

    expect($vista)
        ->toContain('wire:click="descargarPlantillaImportacion"')
        ->toContain('wire:click="importarPlantillaCalificaciones"')
        ->toContain('wire:click="descargarInformeIaPdf"')
        ->toContain('wire:click="descargarInformeIaWord"')
        ->toContain('exportarImagen(')
        ->not->toContain('Actualizando tabla')
        ->not->toContain('backdrop-blur-[3px]');
});
