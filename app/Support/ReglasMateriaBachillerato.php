<?php

namespace App\Support;

use App\Models\Materia;

/**
 * Fuente única de verdad para las materias de bachillerato.
 *
 * - Oficial/promediable: calificable, no extra y no receso.
 * - Extra informativa: extra y no receso. Se captura y se muestra, pero no promedia.
 * - Capturable: oficial o extra informativa; nunca receso.
 */
final class ReglasMateriaBachillerato
{
    public const NIVEL_ID = 4;

    public static function esBachillerato(int|string|null $nivelId): bool
    {
        return (int) $nivelId === self::NIVEL_ID;
    }

    public static function esPromediable(array|object|null $materia): bool
    {
        return self::valorBooleano($materia, 'calificable')
            && ! self::valorBooleano($materia, 'extra')
            && ! self::valorBooleano($materia, 'receso');
    }

    public static function esExtraInformativa(array|object|null $materia): bool
    {
        return self::valorBooleano($materia, 'extra')
            && ! self::valorBooleano($materia, 'receso');
    }

    public static function esCapturable(array|object|null $materia): bool
    {
        return ! self::valorBooleano($materia, 'receso')
            && (
                self::valorBooleano($materia, 'calificable')
                || self::valorBooleano($materia, 'extra')
            );
    }

    /**
     * Aplica la regla oficial a una consulta Eloquent/Query Builder.
     */
    public static function aplicarPromediables(object $query, string $prefijo = 'materias'): object
    {
        $query->where(self::columna($prefijo, 'calificable'), true)
            ->where(self::columna($prefijo, 'extra'), false)
            ->where(self::columna($prefijo, 'receso'), false);

        return $query;
    }

    /**
     * Aplica la regla de materias que pueden tener captura en bachillerato.
     */
    public static function aplicarCapturables(object $query, string $prefijo = 'materias'): object
    {
        $query->where(self::columna($prefijo, 'receso'), false)
            ->where(function ($subQuery) use ($prefijo): void {
                $subQuery->where(self::columna($prefijo, 'calificable'), true)
                    ->orWhere(self::columna($prefijo, 'extra'), true);
            });

        return $query;
    }

    /**
     * Normaliza banderas antes de guardar una materia.
     * No modifica la lógica de otros niveles salvo las invariantes generales de receso.
     */
    public static function normalizarAtributos(array $atributos): array
    {
        $nivelId = (int) ($atributos['nivel_id'] ?? 0);
        $calificable = (bool) ($atributos['calificable'] ?? false);
        $extra = (bool) ($atributos['extra'] ?? false);
        $receso = (bool) ($atributos['receso'] ?? false);
        $oficial = (bool) ($atributos['participa_en_calificacion_oficial'] ?? false);

        if ($receso) {
            $calificable = false;
            $extra = false;
            $oficial = false;
        } elseif (self::esBachillerato($nivelId) && $extra) {
            // En bachillerato una materia extra debe aceptar captura, pero jamás ser oficial.
            $calificable = true;
            $oficial = false;
        } elseif (! $calificable || $extra) {
            $oficial = false;
        }

        $atributos['calificable'] = $calificable;
        $atributos['extra'] = $extra;
        $atributos['receso'] = $receso;
        $atributos['participa_en_calificacion_oficial'] = $oficial;

        return $atributos;
    }

    public static function normalizarModelo(Materia $materia): void
    {
        $normalizados = self::normalizarAtributos([
            'nivel_id' => $materia->nivel_id,
            'calificable' => $materia->calificable,
            'extra' => $materia->extra,
            'receso' => $materia->receso,
            'participa_en_calificacion_oficial' => $materia->participa_en_calificacion_oficial,
        ]);

        $materia->calificable = $normalizados['calificable'];
        $materia->extra = $normalizados['extra'];
        $materia->receso = $normalizados['receso'];
        $materia->participa_en_calificacion_oficial = $normalizados['participa_en_calificacion_oficial'];
    }

    private static function valorBooleano(array|object|null $materia, string $campo): bool
    {
        if (is_array($materia)) {
            return (bool) ($materia[$campo] ?? false);
        }

        if (is_object($materia)) {
            return (bool) ($materia->{$campo} ?? false);
        }

        return false;
    }

    private static function columna(string $prefijo, string $campo): string
    {
        $prefijo = trim($prefijo, '.');

        return $prefijo === '' ? $campo : $prefijo . '.' . $campo;
    }
}
