<?php

namespace App\Support;

final class CurpRenapo
{
    /**
     * Catálogo de entidades usado para interpretar las posiciones 12 y 13
     * de la CURP. Las claves corresponden al catálogo de conformación CURP
     * publicado por RENAPO. QO se conserva como alias de compatibilidad con
     * el mapeo técnico publicado en DOF en 2026; la clave normativa CURP es QT.
     *
     * @var array<string, string>
     */
    private const ENTIDADES = [
        'AS' => 'AGUASCALIENTES',
        'BC' => 'BAJA CALIFORNIA',
        'BS' => 'BAJA CALIFORNIA SUR',
        'CC' => 'CAMPECHE',
        'CL' => 'COAHUILA',
        'CM' => 'COLIMA',
        'CS' => 'CHIAPAS',
        'CH' => 'CHIHUAHUA',
        'DF' => 'CIUDAD DE MÉXICO',
        'DG' => 'DURANGO',
        'GT' => 'GUANAJUATO',
        'GR' => 'GUERRERO',
        'HG' => 'HIDALGO',
        'JC' => 'JALISCO',
        'MC' => 'ESTADO DE MÉXICO',
        'MN' => 'MICHOACÁN',
        'MS' => 'MORELOS',
        'NT' => 'NAYARIT',
        'NL' => 'NUEVO LEÓN',
        'OC' => 'OAXACA',
        'PL' => 'PUEBLA',
        'QT' => 'QUERÉTARO',
        'QO' => 'QUERÉTARO',
        'QR' => 'QUINTANA ROO',
        'SP' => 'SAN LUIS POTOSÍ',
        'SL' => 'SINALOA',
        'SR' => 'SONORA',
        'TC' => 'TABASCO',
        'TS' => 'TAMAULIPAS',
        'TL' => 'TLAXCALA',
        'VZ' => 'VERACRUZ',
        'YN' => 'YUCATÁN',
        'ZS' => 'ZACATECAS',
        'NE' => 'NACIDO EN EL EXTRANJERO',
        'XX' => 'DESCONOCIDO',
    ];

    /**
     * @return array{codigo:?string,nombre:string,reconocida:bool}
     */
    public static function entidadNacimiento(?string $curp): array
    {
        $curpNormalizada = strtoupper(preg_replace('/\s+/', '', trim((string) $curp)) ?? '');

        if (strlen($curpNormalizada) < 13) {
            return [
                'codigo' => null,
                'nombre' => 'NO IDENTIFICADA',
                'reconocida' => false,
            ];
        }

        $codigo = substr($curpNormalizada, 11, 2);
        $nombre = self::ENTIDADES[$codigo] ?? null;

        return [
            'codigo' => $codigo,
            'nombre' => $nombre ?? 'NO IDENTIFICADA',
            'reconocida' => $nombre !== null,
        ];
    }

    public static function etiquetaEntidadNacimiento(?string $curp): string
    {
        $entidad = self::entidadNacimiento($curp);

        if (! $entidad['codigo']) {
            return '—';
        }

        if (! $entidad['reconocida']) {
            return $entidad['codigo'].' - NO IDENTIFICADA';
        }

        return $entidad['codigo'].' - '.$entidad['nombre'];
    }
}
