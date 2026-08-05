<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Identidad institucional interna
    |--------------------------------------------------------------------------
    |
    | Estas direcciones se utilizan exclusivamente como identificadores de
    | acceso. No representan buzones de correo ni intentan enviar mensajes.
    |
    */
    'domain' => env('TEACHER_INTERNAL_DOMAIN', 'profesor.moctezuma.local'),
    'password_length' => 14,
    'require_password_change' => true,
];
