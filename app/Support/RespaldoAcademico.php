<?php

namespace App\Support;

use InvalidArgumentException;

final class RespaldoAcademico
{
    public const VERSION_FORMATO = '1.0';

    public const TIPO_ALUMNOS = 'alumnos';
    public const TIPO_CALIFICACIONES = 'calificaciones';

    /**
     * @return array{
     *     tipo:string,
     *     titulo:string,
     *     descripcion:string,
     *     prefijo_archivo:string,
     *     tablas:array<string,array{hoja:string,descripcion:string,diferidas:array<int,string>}>
     * }
     */
    public static function configuracion(string $tipo): array
    {
        return match ($tipo) {
            self::TIPO_ALUMNOS => [
                'tipo' => self::TIPO_ALUMNOS,
                'titulo' => 'Respaldo integral de alumnos',
                'descripcion' => 'Incluye tutores, alumnos, trayectorias académicas, matrículas históricas y movimientos.',
                'prefijo_archivo' => 'RESPALDO_ALUMNOS',
                'tablas' => [
                    'tutores' => [
                        'hoja' => 'Tutores',
                        'descripcion' => 'Tutores vinculados a los alumnos, conservando sus identificadores.',
                        'diferidas' => [],
                    ],
                    'inscripciones' => [
                        'hoja' => 'Alumnos',
                        'descripcion' => 'Registro principal de alumnos, incluidos activos, bajas y archivados.',
                        'diferidas' => [],
                    ],
                    'trayectorias_academicas' => [
                        'hoja' => 'Trayectorias',
                        'descripcion' => 'Historial por ciclo escolar, corte, nivel, grado, grupo y estancia.',
                        'diferidas' => ['trayectoria_origen_id'],
                    ],
                    'matriculas_alumnos' => [
                        'hoja' => 'Matriculas',
                        'descripcion' => 'Historial de matrículas asignadas por nivel.',
                        'diferidas' => [],
                    ],
                    'movimientos_alumnos' => [
                        'hoja' => 'Movimientos',
                        'descripcion' => 'Línea de tiempo de altas, bajas, traslados, promociones y reingresos.',
                        'diferidas' => [],
                    ],
                ],
            ],
            self::TIPO_CALIFICACIONES => [
                'tipo' => self::TIPO_CALIFICACIONES,
                'titulo' => 'Respaldo integral de calificaciones',
                'descripcion' => 'Incluye todas las calificaciones y su bitácora de cambios.',
                'prefijo_archivo' => 'RESPALDO_CALIFICACIONES',
                'tablas' => [
                    'calificaciones' => [
                        'hoja' => 'Calificaciones',
                        'descripcion' => 'Todas las calificaciones, sin filtros y conservando su ID original.',
                        'diferidas' => [],
                    ],
                    'bitacora_calificaciones' => [
                        'hoja' => 'Bitacora',
                        'descripcion' => 'Auditoría de creación, edición y eliminación de calificaciones.',
                        'diferidas' => [],
                    ],
                ],
            ],
            default => throw new InvalidArgumentException("Tipo de respaldo no válido: {$tipo}"),
        };
    }

    public static function nombreArchivo(string $tipo): string
    {
        $configuracion = self::configuracion($tipo);

        return $configuracion['prefijo_archivo']
            . '_'
            . now()->format('Y-m-d_H-i-s')
            . '.xlsx';
    }
}
