<?php

namespace App\Enums;

enum ResultadoInscripcionCiclo: string
{
    case PROMOVIDO = 'promovido';
    case PROMOVIDO_GRADO = 'promovido_grado';
    case PROMOVIDO_NIVEL = 'promovido_nivel';
    case NO_PROMOVIDO = 'no_promovido';
    case CONTINUIDAD = 'continuidad';
    case EGRESADO = 'egresado';
    case TRASLADADO = 'trasladado';
    case BAJA_TEMPORAL_AL_CIERRE = 'baja_temporal_al_cierre';
    case BAJA_DEFINITIVA = 'baja_definitiva';

    public static function normalizar(?string $valor): ?string
    {
        $valor = mb_strtolower(trim((string) $valor));

        if ($valor === '') {
            return null;
        }

        return match ($valor) {
            'traslado' => self::TRASLADADO->value,
            'repetidor' => self::NO_PROMOVIDO->value,
            default => $valor,
        };
    }

    public static function esPromocion(?string $valor): bool
    {
        return in_array(self::normalizar($valor), [
            self::PROMOVIDO->value,
            self::PROMOVIDO_GRADO->value,
            self::PROMOVIDO_NIVEL->value,
        ], true);
    }
}
