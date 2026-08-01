<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lector local de identificaciones de tutores
    |--------------------------------------------------------------------------
    |
    | No utiliza APIs ni servicios con costo. Los PDF con texto seleccionable
    | se leen directamente. Para imágenes o PDF escaneados se utiliza
    | Tesseract OCR instalado en el mismo equipo/servidor.
    |
    */
    'enabled' => (bool) env('TUTOR_OCR_ENABLED', true),

    'tesseract_binary' => env('TESSERACT_BINARY'),
    'tesseract_language' => env('TESSERACT_LANGUAGE', 'spa+eng'),

    // Necesario únicamente para convertir PDF escaneados a imágenes.
    'pdftoppm_binary' => env('PDFTOPPM_BINARY'),

    // Opcional: mejora rotación, contraste y tamaño antes del OCR.
    'imagemagick_binary' => env('IMAGEMAGICK_BINARY'),

    'timeout' => (int) env('TUTOR_OCR_TIMEOUT', 90),
    'max_pages_pdf' => (int) env('TUTOR_OCR_MAX_PAGES', 2),
    'max_file_kb' => (int) env('TUTOR_OCR_MAX_FILE_KB', 12288),
    'minimum_text_length' => (int) env('TUTOR_OCR_MIN_TEXT_LENGTH', 35),
    'show_raw_text' => (bool) env('TUTOR_OCR_SHOW_RAW_TEXT', false),
];
