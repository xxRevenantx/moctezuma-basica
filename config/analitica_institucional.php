<?php

declare(strict_types=1);

return [
    'snapshot_schedule' => env('ANALITICA_SNAPSHOT_SCHEDULE', '07:00'),
    'trend_cycles' => (int) env('ANALITICA_TREND_CYCLES', 5),
    'passing_grade' => (float) env('ANALITICA_PASSING_GRADE', 6),
    'alerts' => [
        'matricula_caida_porcentaje' => (float) env('ANALITICA_ALERTA_MATRICULA_CAIDA', 5),
        'riesgo_alto_porcentaje' => (float) env('ANALITICA_ALERTA_RIESGO_ALTO', 10),
        'documentacion_minima_porcentaje' => (float) env('ANALITICA_ALERTA_DOCUMENTACION_MINIMA', 85),
        'permanencia_minima_porcentaje' => (float) env('ANALITICA_ALERTA_PERMANENCIA_MINIMA', 90),
    ],
];
