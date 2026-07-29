<?php

use App\Models\ProcesoCierreCiclo;
use App\Models\ProcesoCierreCicloDetalle;
use App\Models\SimulacionCierreCiclo;
use App\Services\CierreGeneracionContinuidadService;

it('mantiene estable la firma aunque cambie el orden de las llaves', function (): void {
    $service = app(CierreGeneracionContinuidadService::class);
    $metodo = new ReflectionMethod($service, 'firmarContenido');
    $metodo->setAccessible(true);

    $primero = $metodo->invoke($service, [
        'configuracion' => ['ciclo' => 12, 'nivel' => 4],
        'alumnos' => [
            ['id' => 8, 'resultado' => 'egresado'],
            ['id' => 2, 'resultado' => 'traslado'],
        ],
    ]);
    $segundo = $metodo->invoke($service, [
        'alumnos' => [
            ['resultado' => 'egresado', 'id' => 8],
            ['resultado' => 'traslado', 'id' => 2],
        ],
        'configuracion' => ['nivel' => 4, 'ciclo' => 12],
    ]);

    expect($primero)
        ->toBeString()
        ->toHaveLength(64)
        ->toBe($segundo);
});

it('convierte simulaciones y respaldos json a arreglos', function (): void {
    $simulacion = new SimulacionCierreCiclo();
    $simulacion->setRawAttributes([
        'contenido' => '{"alumnos":[1,2]}',
        'resumen' => '{"total":2}',
    ]);

    $proceso = new ProcesoCierreCiclo();
    $proceso->setRawAttributes([
        'simulacion' => '{"hash":"abc"}',
        'respaldo_logico' => '{"version":1}',
        'reversion_resumen' => '{"restaurados":2}',
    ]);

    $detalle = new ProcesoCierreCicloDetalle();
    $detalle->setRawAttributes([
        'respaldo_origen' => '{"inscripcion_id":9}',
    ]);

    expect($simulacion->contenido)->toBe(['alumnos' => [1, 2]])
        ->and($simulacion->resumen)->toBe(['total' => 2])
        ->and($proceso->simulacion)->toBe(['hash' => 'abc'])
        ->and($proceso->respaldo_logico)->toBe(['version' => 1])
        ->and($proceso->reversion_resumen)->toBe(['restaurados' => 2])
        ->and($detalle->respaldo_origen)->toBe(['inscripcion_id' => 9]);
});

it('solo marca como reversible un proceso completado y vigente', function (): void {
    $proceso = new ProcesoCierreCiclo(['estado' => 'completado']);
    expect($proceso->puede_revertirse)->toBeTrue();

    $proceso->estado = 'revertido';
    expect($proceso->puede_revertirse)->toBeFalse();

    $proceso->estado = 'completado';
    $proceso->revertido_at = now();
    expect($proceso->puede_revertirse)->toBeFalse();
});
