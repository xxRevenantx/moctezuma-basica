<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Credenciales en imagen
    |--------------------------------------------------------------------------
    |
    | Se conserva la resolución real de las plantillas institucionales.
    | Las descargas masivas se limitan para evitar agotar memoria o tiempo de
    | ejecución. El JPG se genera a calidad 100, según la configuración elegida.
    |
    */
    'max_imagenes_por_zip' => env('CREDENCIALES_MAX_IMAGENES_ZIP', 250),
    'max_copias_por_alumno' => env('CREDENCIALES_MAX_COPIAS_POR_ALUMNO', 10),
    'umbral_confirmacion_copias' => env('CREDENCIALES_UMBRAL_CONFIRMACION', 100),
    'jpg_quality' => 100,
    'png_compression' => 6,
];
