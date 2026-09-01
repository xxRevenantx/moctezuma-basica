<?php

declare(strict_types=1);

/**
 * Moctezuma Básica
 * Sincronización inmediata de anulaciones administrativas con continuidad.
 *
 * Colocar ESTE archivo en la raíz del proyecto:
 * C:\laragon\www\moctezuma-basica
 *
 * Ejecutar:
 * php sincronizar_anulaciones_continuidad.php
 */

$root = __DIR__;

$autoload = $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
$bootstrap = $root . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

if (!is_file($autoload)) {
    fwrite(STDERR, "ERROR: No se encontró vendor/autoload.php.\n");
    fwrite(STDERR, "Coloca este archivo en la raíz del proyecto Moctezuma Básica.\n");
    exit(1);
}

if (!is_file($bootstrap)) {
    fwrite(STDERR, "ERROR: No se encontró bootstrap/app.php.\n");
    fwrite(STDERR, "Coloca este archivo en la raíz del proyecto Moctezuma Básica.\n");
    exit(1);
}

require $autoload;

/** @var \Illuminate\Foundation\Application $app */
$app = require $bootstrap;

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    if (!class_exists(\App\Services\CierreGeneracionContinuidadService::class)) {
        throw new RuntimeException(
            'No se encontró CierreGeneracionContinuidadService.'
        );
    }

    $service = app(\App\Services\CierreGeneracionContinuidadService::class);

    if (!method_exists($service, 'sincronizarAnulacionesAdministrativas')) {
        throw new RuntimeException(
            'El servicio todavía no contiene sincronizarAnulacionesAdministrativas(). '
            . 'Primero aplica la integración de sincronización.'
        );
    }

    $cantidad = $service->sincronizarAnulacionesAdministrativas(
        null,
        auth()->id() ? (int) auth()->id() : null
    );

    \Illuminate\Support\Facades\Artisan::call('optimize:clear');

    echo "\n============================================================\n";
    echo "SINCRONIZACIÓN COMPLETADA\n";
    echo "============================================================\n";
    echo "Proyecciones reparadas ahora: {$cantidad}\n";
    echo "Cachés limpiadas: OK\n";
    echo "No se modificó el ciclo de origen.\n";
    echo "No se requiere migración.\n\n";
    echo "Abre nuevamente Cierre y continuidad.\n";
    echo "Las continuidades asociadas a un destino anulado administrativamente\n";
    echo "deben mostrarse como: ANULADA ADMINISTRATIVAMENTE.\n";

    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "\nERROR: " . $e->getMessage() . "\n");
    exit(1);
}
