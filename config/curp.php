<?php

return [
    'base_url' => env('CURP_API_URL', 'https://api.valida-curp.com.mx/curp/obtener_datos/'),
    'token' => env('CURP_API_TOKEN'),
    'timeout' => (int) env('CURP_API_TIMEOUT', 15),
    'connect_timeout' => (int) env('CURP_API_CONNECT_TIMEOUT', 5),
];
