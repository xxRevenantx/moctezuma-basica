<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CurpService
{
    // URL real
    protected string $baseUrl = 'https://api.valida-curp.com.mx/curp/obtener_datos/';

    protected string $token = 'pruebas';
    // protected string $token = '8d51c37a-87b1-40c9-8ae6-7b5651406d1f';

    /**
     * Detecto si estoy en modo pruebas
     */
    public function esModoPruebas(): bool
    {
        return $this->token === 'pruebas';
    }

    public function obtenerDatosPorCurp(string $curp): array
    {
        $curp = mb_strtoupper(trim($curp));

        // Si estoy en modo pruebas, puedo regresar datos fake
        if ($this->esModoPruebas() && method_exists($this, 'fakeResponse')) {
            return $this->fakeResponse($curp);
        }

        $response = Http::acceptJson()->get($this->baseUrl, [
            'token' => $this->token,
            'curp' => $curp,
        ]);

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        return [
            'error' => true,
            'message' => 'CURP inválido o error de conexión',
            'status' => $response->status(),
        ];
    }

    /**
     * Saco el sexo de la CURP (posición 11): H o M.
     * Si no se puede leer, regreso null.
     */
    protected function obtenerSexoDeCurp(string $curp): ?string
    {
        // Validación mínima: CURP de 18 caracteres
        if (mb_strlen($curp) !== 18) {
            return null;
        }

        // Posición 11 (1-indexed) = índice 10 (0-indexed)
        $sexo = mb_substr($curp, 10, 1);

        if ($sexo === 'H' || $sexo === 'M') {
            return $sexo;
        }

        return null;
    }

    protected function fakeResponse(string $curp): array
    {
        $seed = abs(crc32($curp));
        mt_srand($seed);

        // Listas separadas para evitar mezclar nombres
        $nombresHombre = ['CARLOS', 'ALBERTO', 'JUAN', 'PEDRO', 'ANGEL', 'DANIEL', 'MIGUEL', 'JOSE', 'LUIS', 'FERNANDO'];
        $nombresMujer = ['MARIA', 'MELISA', 'PAOLA', 'YULISA', 'KARLA', 'ANDREA', 'SOFIA', 'DANIELA', 'FERNANDA', 'VALERIA'];

        $apellidos = ['NUNEZ', 'PEREZ', 'GARCIA', 'HERNANDEZ', 'LOPEZ', 'MARTINEZ', 'SANCHEZ', 'RAMIREZ', 'FLORES', 'TORRES'];

        // Intento tomar el sexo desde la CURP; si no, lo hago aleatorio
        $claveSexo = $this->obtenerSexoDeCurp($curp) ?? ((mt_rand(0, 1) === 0) ? 'H' : 'M');

        // Selecciono la lista correcta según el sexo
        $listaNombres = ($claveSexo === 'H') ? $nombresHombre : $nombresMujer;

        $nombre1 = $listaNombres[mt_rand(0, count($listaNombres) - 1)];
        $nombre2 = $listaNombres[mt_rand(0, count($listaNombres) - 1)];

        $apellidoP = $apellidos[mt_rand(0, count($apellidos) - 1)];
        $apellidoM = $apellidos[mt_rand(0, count($apellidos) - 1)];

        $sexoTexto = ($claveSexo === 'H') ? 'Hombre' : 'Mujer';

        $year = mt_rand(1985, 2006);
        $month = str_pad((string) mt_rand(1, 12), 2, '0', STR_PAD_LEFT);
        $day = str_pad((string) mt_rand(1, 28), 2, '0', STR_PAD_LEFT);
        $fechaNacimiento = "{$year}-{$month}-{$day}";

        return [
            'error' => false,
            'code_error' => 0,
            'error_message' => '',
            'response' => [
                'Solicitante' => [
                    'CURP' => $curp,
                    'Nombres' => "{$nombre1} {$nombre2}",
                    'ApellidoPaterno' => $apellidoP,
                    'ApellidoMaterno' => $apellidoM,
                    'ClaveSexo' => $claveSexo,
                    'Sexo' => $sexoTexto,
                    'FechaNacimiento' => $fechaNacimiento,
                    'Nacionalidad' => 'MEX',
                    'ClaveEntidadNacimiento' => 'GR',
                    'EntidadNacimiento' => 'Guerrero',
                ],
            ],
        ];
    }
}
