<?php

namespace App\Enums;

enum EstatusAlumnoCiclo: string
{
    case PREINSCRITO = 'preinscrito';
    case ACTIVO = 'activo';
    case REINGRESO = 'reingreso';
    case NO_PROMOVIDO = 'no_promovido';
    case PENDIENTE_REINSCRIPCION = 'pendiente_reinscripcion';
    case NO_REINSCRITO = 'no_reinscrito';
    case BAJA_TEMPORAL = 'baja_temporal';
    case BAJA_DEFINITIVA = 'baja_definitiva';
    case TRASLADADO = 'trasladado';
    case EGRESADO = 'egresado';
    case SUSPENDIDO = 'suspendido';
    case INACTIVO = 'inactivo';

    public static function normalizar(?string $valor, bool $activo = true): string
    {
        $valor = mb_strtolower(trim((string) $valor));

        if ($valor === '') {
            return $activo ? self::ACTIVO->value : self::INACTIVO->value;
        }

        return match ($valor) {
            'traslado' => self::TRASLADADO->value,
            'baja' => self::BAJA_DEFINITIVA->value,
            default => $valor,
        };
    }

    public static function esVigente(?string $valor): bool
    {
        return in_array(self::normalizar($valor), [
            self::ACTIVO->value,
            self::REINGRESO->value,
            self::NO_PROMOVIDO->value,
        ], true);
    }

    public static function esSalidaDefinitiva(?string $valor): bool
    {
        return in_array(self::normalizar($valor), [
            self::BAJA_DEFINITIVA->value,
            self::TRASLADADO->value,
            self::EGRESADO->value,
        ], true);
    }

    public static function estatusIngresoSeguro(?string $valor): string
    {
        $valor = self::normalizar($valor);

        return match ($valor) {
            self::REINGRESO->value => self::REINGRESO->value,
            self::NO_PROMOVIDO->value => self::NO_PROMOVIDO->value,
            default => self::ACTIVO->value,
        };
    }
}
