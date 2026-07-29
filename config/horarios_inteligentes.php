<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Ejecutable de Python
    |--------------------------------------------------------------------------
    |
    | En producción se recomienda utilizar la ruta absoluta del Python
    | perteneciente al entorno virtual del optimizador de horarios.
    |
    */

    'python_bin' => env(
        'HORARIOS_PYTHON_BIN',
        PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3'
    ),

    /*
    |--------------------------------------------------------------------------
    | Script optimizador
    |--------------------------------------------------------------------------
    */

    'solver_script' => env(
        'HORARIOS_SCRIPT_PATH',
        base_path('scripts/horarios/optimize.py')
    ),

    /*
    |--------------------------------------------------------------------------
    | Tiempo máximo del proceso
    |--------------------------------------------------------------------------
    */

    'timeout_seconds' => (int) env(
        'HORARIOS_SOLVER_TIMEOUT',
        90
    ),

    /*
    |--------------------------------------------------------------------------
    | Tiempo asignado a cada objetivo
    |--------------------------------------------------------------------------
    */

    'seconds_per_objective' => (int) env(
        'HORARIOS_SOLVER_SECONDS_PER_OBJECTIVE',
        12
    ),

    /*
    |--------------------------------------------------------------------------
    | Duración mínima de un módulo
    |--------------------------------------------------------------------------
    */

    'min_slot_minutes' => (int) env(
        'HORARIOS_MIN_SLOT_MINUTES',
        40
    ),

    /*
    |--------------------------------------------------------------------------
    | Cantidad máxima de propuestas
    |--------------------------------------------------------------------------
    */

    'max_propuestas' => (int) env(
        'HORARIOS_MAX_PROPUESTAS',
        4
    ),

];
