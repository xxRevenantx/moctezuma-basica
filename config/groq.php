<?php

return [
    'enabled' => filter_var(env('GROQ_ENABLED', true), FILTER_VALIDATE_BOOL),

    'api_key' => env('GROQ_API_KEY'),

    'base_url' => rtrim(env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'), '/'),

    'model' => env('GROQ_MODEL', 'llama-3.1-8b-instant'),

    'connect_timeout' => (int) env('GROQ_CONNECT_TIMEOUT', 8),
    'status_timeout' => (int) env('GROQ_STATUS_TIMEOUT', 15),
    'request_timeout' => (int) env('GROQ_REQUEST_TIMEOUT', 60),

    'temperature' => (float) env('GROQ_TEMPERATURE', 0.35),
    'max_tokens' => (int) env('GROQ_MAX_TOKENS', 450),

    // Por privacidad, permanece desactivado de forma predeterminada.
    // Cuando está activo, las recomendaciones pueden usar como contexto
    // los demás campos formativos guardados del mismo alumno.
    'include_context' => filter_var(env('GROQ_INCLUDE_CONTEXT', false), FILTER_VALIDATE_BOOL),
];
