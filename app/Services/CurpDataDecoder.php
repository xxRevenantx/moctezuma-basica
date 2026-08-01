<?php

namespace App\Services;

class CurpDataDecoder
{
    /** @var array<string, string> */
    private const ENTIDADES = [
        'AS' => 'Aguascalientes',
        'BC' => 'Baja California',
        'BS' => 'Baja California Sur',
        'CC' => 'Campeche',
        'CL' => 'Coahuila',
        'CM' => 'Colima',
        'CS' => 'Chiapas',
        'CH' => 'Chihuahua',
        'DF' => 'Ciudad de México',
        'DG' => 'Durango',
        'GT' => 'Guanajuato',
        'GR' => 'Guerrero',
        'HG' => 'Hidalgo',
        'JC' => 'Jalisco',
        'MC' => 'Estado de México',
        'MN' => 'Michoacán',
        'MS' => 'Morelos',
        'NT' => 'Nayarit',
        'NL' => 'Nuevo León',
        'OC' => 'Oaxaca',
        'PL' => 'Puebla',
        'QT' => 'Querétaro',
        'QR' => 'Quintana Roo',
        'SP' => 'San Luis Potosí',
        'SL' => 'Sinaloa',
        'SR' => 'Sonora',
        'TC' => 'Tabasco',
        'TS' => 'Tamaulipas',
        'TL' => 'Tlaxcala',
        'VZ' => 'Veracruz',
        'YN' => 'Yucatán',
        'ZS' => 'Zacatecas',
        'NE' => 'Nacido en el extranjero',
    ];

    public function __construct(
        private readonly CurpLocalLookupService $validator
    ) {
    }

    /**
     * @return array{valida: bool, curp: string, fecha_nacimiento: ?string, genero: ?string, estado_nacimiento: ?string, mensaje: string}
     */
    public function decode(?string $value): array
    {
        $curp = $this->validator->normalizar($value);
        $validation = $this->validator->validarFormato($curp);

        if (! $validation['valida']) {
            return [
                'valida' => false,
                'curp' => $curp,
                'fecha_nacimiento' => null,
                'genero' => null,
                'estado_nacimiento' => null,
                'mensaje' => $validation['mensaje'],
            ];
        }

        $shortYear = (int) substr($curp, 4, 2);
        $month = (int) substr($curp, 6, 2);
        $day = (int) substr($curp, 8, 2);
        $centuryMarker = substr($curp, 16, 1);
        $year = (ctype_digit($centuryMarker) ? 1900 : 2000) + $shortYear;

        $birthDate = checkdate($month, $day, $year)
            ? sprintf('%04d-%02d-%02d', $year, $month, $day)
            : null;

        if ($birthDate !== null && $birthDate > date('Y-m-d')) {
            return [
                'valida' => false,
                'curp' => $curp,
                'fecha_nacimiento' => null,
                'genero' => null,
                'estado_nacimiento' => null,
                'mensaje' => 'La fecha interpretada desde la CURP se encuentra en el futuro.',
            ];
        }

        $curpGender = substr($curp, 10, 1);
        $tutorGender = match ($curpGender) {
            'H' => 'M', // Hombre -> Masculino en la tabla tutores.
            'M' => 'F', // Mujer -> Femenino en la tabla tutores.
            default => null,
        };

        $entityCode = substr($curp, 11, 2);

        return [
            'valida' => true,
            'curp' => $curp,
            'fecha_nacimiento' => $birthDate,
            'genero' => $tutorGender,
            'estado_nacimiento' => self::ENTIDADES[$entityCode] ?? null,
            'mensaje' => 'CURP interpretada localmente; no se realizó ninguna consulta externa.',
        ];
    }

    public function entityName(string $code): ?string
    {
        return self::ENTIDADES[strtoupper(trim($code))] ?? null;
    }
}
