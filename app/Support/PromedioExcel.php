<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Reglas de cálculo equivalentes a PROMEDIO de Excel:
 *
 * 1. Solo participan valores numéricos válidos.
 * 2. Vacíos, textos y claves especiales no suman ni cuentan como divisor.
 * 3. Nunca se truncan resultados intermedios.
 * 4. El truncamiento a un decimal se aplica únicamente al valor que se muestra.
 */
final class PromedioExcel
{
    public const MIN_CALIFICACION = 0.0;

    public const MAX_CALIFICACION = 10.0;

    private const EPSILON = 0.000000001;

    /**
     * @param iterable<mixed> $valores
     */
    public static function valoresNumericos(
        iterable $valores,
        float $minimo = self::MIN_CALIFICACION,
        float $maximo = self::MAX_CALIFICACION,
    ): Collection {
        return collect($valores)
            ->filter(static function (mixed $valor) use ($minimo, $maximo): bool {
                if ($valor === null || $valor === '' || ! is_numeric($valor)) {
                    return false;
                }

                $numero = (float) $valor;

                return $numero >= $minimo && $numero <= $maximo;
            })
            ->map(static fn (mixed $valor): float => (float) $valor)
            ->values();
    }

    /**
     * Devuelve el promedio con toda su precisión. No trunca ni redondea.
     *
     * @param iterable<mixed> $valores
     */
    public static function calcular(
        iterable $valores,
        float $minimo = self::MIN_CALIFICACION,
        float $maximo = self::MAX_CALIFICACION,
    ): ?float {
        $numericos = self::valoresNumericos($valores, $minimo, $maximo);

        if ($numericos->isEmpty()) {
            return null;
        }

        return (float) ($numericos->sum() / $numericos->count());
    }

    public static function truncar(null|int|float|string $valor, int $decimales = 1): ?float
    {
        if ($valor === null || $valor === '' || ! is_numeric($valor)) {
            return null;
        }

        $factor = 10 ** max(0, $decimales);

        return floor((((float) $valor) + self::EPSILON) * $factor) / $factor;
    }

    public static function formatear(
        null|int|float|string $valor,
        int $decimales = 1,
        string $vacio = '—',
    ): string {
        $truncado = self::truncar($valor, $decimales);

        if ($truncado === null) {
            return $vacio;
        }

        return number_format($truncado, $decimales, '.', '');
    }

    /**
     * Clave de comparación con precisión suficiente para ordenar y resolver empates
     * sin usar el promedio ya truncado para presentación.
     */
    public static function claveComparacion(null|int|float|string $valor, int $decimales = 9): ?string
    {
        if ($valor === null || $valor === '' || ! is_numeric($valor)) {
            return null;
        }

        return number_format((float) $valor, $decimales, '.', '');
    }
}
