<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Reglas institucionales para las calificaciones de bachillerato.
 *
 * - Cada parcial numérico se interpreta como entero truncado, sin redondear.
 * - El promedio de una materia se calcula con los parciales ya truncados y
 *   también se trunca a entero.
 * - El promedio semestral se calcula con los promedios enteros de las
 *   materias y conserva sus decimales.
 * - El promedio anual usa los promedios semestrales sin truncarlos.
 */
final class CalificacionBachillerato
{
    private const EPSILON = 0.000000001;

    public static function truncarParcial(mixed $valor): ?int
    {
        if ($valor === null || $valor === '' || ! is_numeric($valor)) {
            return null;
        }

        $numero = (float) $valor;

        if ($numero < PromedioExcel::MIN_CALIFICACION || $numero > PromedioExcel::MAX_CALIFICACION) {
            return null;
        }

        return (int) floor($numero + self::EPSILON);
    }

    public static function esEnteraValida(mixed $valor): bool
    {
        if ($valor === null || $valor === '' || ! is_numeric($valor)) {
            return false;
        }

        $numero = (float) $valor;

        return $numero >= PromedioExcel::MIN_CALIFICACION
            && $numero <= PromedioExcel::MAX_CALIFICACION
            && abs($numero - round($numero)) < self::EPSILON;
    }

    /**
     * @param iterable<mixed> $parciales
     */
    public static function parcialesEnteros(iterable $parciales): Collection
    {
        return collect($parciales)
            ->map(static fn (mixed $valor): ?int => self::truncarParcial($valor))
            ->filter(static fn (?int $valor): bool => $valor !== null)
            ->values();
    }

    /**
     * Calcula el promedio de materia con los parciales ya truncados y elimina
     * la parte decimal del resultado final, sin redondear.
     *
     * Ejemplo: 8.9 y 9.9 => 8 y 9 => 8.5 => 8.
     *
     * @param iterable<mixed> $parciales
     */
    public static function promedioMateria(iterable $parciales): ?int
    {
        $enteros = self::parcialesEnteros($parciales);

        if ($enteros->isEmpty()) {
            return null;
        }

        return (int) floor(($enteros->sum() / $enteros->count()) + self::EPSILON);
    }

    /**
     * @param iterable<mixed> $promediosMaterias
     */
    public static function promedioSemestral(iterable $promediosMaterias): ?float
    {
        return PromedioExcel::calcular($promediosMaterias);
    }

    public static function formatearEntero(mixed $valor, string $vacio = '—'): string
    {
        $entero = self::truncarParcial($valor);

        return $entero === null ? $vacio : (string) $entero;
    }
}
