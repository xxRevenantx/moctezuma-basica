<?php

return [
    'enabled' => filter_var(env('GROQ_ENABLED', true), FILTER_VALIDATE_BOOL),

    'api_key' => env('GROQ_API_KEY'),

    'base_url' => rtrim(env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'), '/'),

    'model' => env('GROQ_MODEL', 'openai/gpt-oss-20b'),

    'connect_timeout' => (int) env('GROQ_CONNECT_TIMEOUT', 8),
    'status_timeout' => (int) env('GROQ_STATUS_TIMEOUT', 15),
    'request_timeout' => (int) env('GROQ_REQUEST_TIMEOUT', 60),

    'temperature' => (float) env('GROQ_TEMPERATURE', 0.35),
    'max_tokens' => (int) env('GROQ_MAX_TOKENS', 450),

    'calificaciones' => [
        'model' => env('GROQ_CALIFICACION_MODEL', 'openai/gpt-oss-20b'),
        'temperature' => (float) env('GROQ_CALIFICACION_TEMPERATURE', 0.25),
        'max_tokens' => (int) env('GROQ_CALIFICACION_MAX_TOKENS', 1600),
    ],

    'fichas_grupo' => [
        'model' => env('GROQ_FICHA_GRUPO_MODEL', 'openai/gpt-oss-20b'),
        'temperature' => (float) env('GROQ_FICHA_GRUPO_TEMPERATURE', 0.25),
        'max_tokens' => (int) env('GROQ_FICHA_GRUPO_MAX_TOKENS', 2200),
        'max_fragmentos_por_campo' => (int) env('GROQ_FICHA_GRUPO_MAX_FRAGMENTOS', 24),
        'max_caracteres_fragmento' => (int) env('GROQ_FICHA_GRUPO_MAX_CARACTERES_FRAGMENTO', 650),
        'max_caracteres_totales' => (int) env('GROQ_FICHA_GRUPO_MAX_CARACTERES_TOTALES', 28000),
    ],

    'horarios' => [
        'model' => env('GROQ_HORARIO_MODEL', 'openai/gpt-oss-20b'),
        'temperature' => (float) env('GROQ_HORARIO_TEMPERATURE', 0.20),
        'conflicto_max_tokens' => (int) env('GROQ_HORARIO_CONFLICTO_MAX_TOKENS', 1200),
        'analisis_max_tokens' => (int) env('GROQ_HORARIO_ANALISIS_MAX_TOKENS', 1600),
        'max_alternativas' => (int) env('GROQ_HORARIO_MAX_ALTERNATIVAS', 8),
    ],

    'curp' => [
        'enabled' => filter_var(env('GROQ_CURP_ENABLED', true), FILTER_VALIDATE_BOOL),
        'model' => env('GROQ_CURP_MODEL', 'openai/gpt-oss-20b'),
        'temperature' => (float) env('GROQ_CURP_TEMPERATURE', 0),
        'max_tokens' => (int) env('GROQ_CURP_MAX_TOKENS', 700),
        'max_input_chars' => (int) env('GROQ_CURP_MAX_INPUT_CHARS', 12000),
        'min_confidence' => (int) env('GROQ_CURP_MIN_CONFIDENCE', 65),
    ],

    'dashboard' => [
        'model' => env('GROQ_DASHBOARD_MODEL', 'openai/gpt-oss-20b'),
        'temperature' => (float) env('GROQ_DASHBOARD_TEMPERATURE', 0.20),
        'max_tokens' => (int) env('GROQ_DASHBOARD_MAX_TOKENS', 1900),
    ],

    // Por privacidad, permanece desactivado de forma predeterminada.
    // Cuando está activo, las recomendaciones pueden usar como contexto
    // los demás campos formativos guardados del mismo alumno.
    'include_context' => filter_var(env('GROQ_INCLUDE_CONTEXT', false), FILTER_VALIDATE_BOOL),
];
