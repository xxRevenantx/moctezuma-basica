<?php

return [
    'disk' => env('EXPEDIENTES_DISK', config('filesystems.expedientes_disk', 'local')),

    'max_upload_mb' => (int) env('EXPEDIENTES_ORGANIZADOR_MAX_MB', 30),
    'max_pages' => (int) env('EXPEDIENTES_ORGANIZADOR_MAX_PAGES', 50),

    'queue_threshold_mb' => (int) env('EXPEDIENTES_ORGANIZADOR_QUEUE_MB', 20),
    'queue_threshold_pages' => (int) env('EXPEDIENTES_ORGANIZADOR_QUEUE_PAGES', 30),

    'preview_ttl_hours' => (int) env('EXPEDIENTES_ORGANIZADOR_PREVIEW_TTL_HOURS', 24),

    'pdf_normalization' => [
        'enabled' => (bool) env('EXPEDIENTES_PDF_NORMALIZATION_ENABLED', true),
        'timeout_seconds' => (int) env('EXPEDIENTES_PDF_NORMALIZATION_TIMEOUT', 45),
        // Déjalos vacíos para autodetección. En Laragon/Windows puedes indicar
        // la ruta completa cuando qpdf o Ghostscript no estén en el PATH.
        'qpdf_binary' => env('EXPEDIENTES_QPDF_BINARY'),
        'ghostscript_binary' => env('EXPEDIENTES_GHOSTSCRIPT_BINARY'),
    ],

    'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
    'allowed_mimetypes' => [
        'application/pdf',
        'application/x-pdf',
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
    ],

    // Los archivos emitidos internamente no deben dividirse ni reorganizarse.
    'protected_origins' => ['generado'],

    // Tipos externos que sí admiten organización. Los demás tipos generales
    // activos también se incorporan dinámicamente.
    'academic_slugs' => [
        'boleta-final-grado',
        'constancia-estudios',
        'constancia-baja-traslado',
        'constancia-traslado-calificaciones',
        'certificado-estudios',
        'certificado-terminacion',
    ],
];
