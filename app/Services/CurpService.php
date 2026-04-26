<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CurpService
{
    protected string $baseUrl = 'https://api.valida-curp.com.mx/curp/obtener_datos/';

    // protected string $token = 'pruebas';

    protected string $token = '8d51c37a-87b1-40c9-8ae6-7b5651406d1f';

    /**
     * Detecta si se está usando el modo de pruebas.
     */
    public function esModoPruebas(): bool
    {
        return $this->token === 'pruebas';
    }

    /**
     * Consulta los datos por CURP.
     *
     * Se regresa una estructura compatible con tu componente:
     * - error
     * - message
     * - response.Solicitante
     * - datos
     */
    public function obtenerDatosPorCurp(string $curp): array
    {
        $curp = mb_strtoupper(trim($curp));

        // dd($curp);

        if (mb_strlen($curp) !== 18) {
            return [
                'error' => true,
                'message' => 'La CURP debe tener 18 caracteres.',
                'response' => null,
                'datos' => null,
            ];
        }

        if (!preg_match('/^[A-Z0-9]{18}$/', $curp)) {
            return [
                'error' => true,
                'message' => 'Formato de CURP inválido.',
                'response' => null,
                'datos' => null,
            ];
        }

        if ($this->esModoPruebas()) {
            return $this->normalizarRespuesta($this->fakeResponse($curp));
        }

        try {
            $response = Http::acceptJson()
                ->timeout(15)
                ->get($this->baseUrl, [
                    'token' => $this->token,
                    'curp' => $curp,
                ]);

            if (!$response->successful()) {
                return [
                    'error' => true,
                    'message' => 'No se pudo consultar la CURP. Intenta nuevamente.',
                    'status' => $response->status(),
                    'response' => null,
                    'datos' => null,
                ];
            }

            return $this->normalizarRespuesta($response->json() ?? []);

        } catch (\Throwable $e) {
            return [
                'error' => true,
                'message' => 'Error de conexión al consultar la CURP.',
                'exception' => $e->getMessage(),
                'response' => null,
                'datos' => null,
            ];
        }
    }

    /**
     * Normaliza la respuesta para que siempre tenga la misma estructura.
     */
    protected function normalizarRespuesta(array $respuesta): array
    {
        if (($respuesta['error'] ?? true) === true) {
            return [
                'error' => true,
                'message' => $respuesta['error_message']
                    ?? $respuesta['message']
                    ?? 'No se encontraron datos para la CURP.',
                'response' => $respuesta['response'] ?? null,
                'datos' => null,
                'raw' => $respuesta,
            ];
        }

        $solicitante = data_get($respuesta, 'response.Solicitante');

        if (!$solicitante || !is_array($solicitante)) {
            return [
                'error' => true,
                'message' => 'La respuesta no contiene datos del solicitante.',
                'response' => $respuesta['response'] ?? null,
                'datos' => null,
                'raw' => $respuesta,
            ];
        }

        $curp = trim((string) data_get($solicitante, 'CURP', ''));
        $nombres = trim((string) data_get($solicitante, 'Nombres', ''));
        $apellidoPaterno = trim((string) data_get($solicitante, 'ApellidoPaterno', ''));
        $apellidoMaterno = trim((string) data_get($solicitante, 'ApellidoMaterno', ''));
        $claveSexo = mb_strtoupper(trim((string) data_get($solicitante, 'ClaveSexo', '')));
        $sexo = trim((string) data_get($solicitante, 'Sexo', ''));
        $fechaNacimiento = trim((string) data_get($solicitante, 'FechaNacimiento', ''));

        return [
            'error' => false,
            'message' => 'Datos encontrados correctamente.',
            'response' => [
                'Solicitante' => [
                    'CURP' => $curp,
                    'Nombres' => $nombres,
                    'ApellidoPaterno' => $apellidoPaterno,
                    'ApellidoMaterno' => $apellidoMaterno,
                    'ClaveSexo' => $claveSexo,
                    'Sexo' => $sexo,
                    'FechaNacimiento' => $fechaNacimiento,
                    'Nacionalidad' => trim((string) data_get($solicitante, 'Nacionalidad', '')),
                    'ClaveEntidadNacimiento' => trim((string) data_get($solicitante, 'ClaveEntidadNacimiento', '')),
                    'EntidadNacimiento' => trim((string) data_get($solicitante, 'EntidadNacimiento', '')),
                ],
            ],
            'datos' => [
                'curp' => $curp,
                'nombre' => $nombres,
                'apellido_paterno' => $apellidoPaterno,
                'apellido_materno' => $apellidoMaterno,
                'genero' => $claveSexo,
                'sexo' => $sexo,
                'fecha_nacimiento' => $fechaNacimiento,
                'pais_nacimiento' => trim((string) data_get($solicitante, 'Nacionalidad', '')),
                'estado_nacimiento' => trim((string) data_get($solicitante, 'EntidadNacimiento', '')),
                'lugar_nacimiento' => trim((string) data_get($solicitante, 'EntidadNacimiento', '')),
            ],
            'raw' => $respuesta,
        ];
    }

    /**
     * Obtiene el sexo desde la CURP.
     */
    protected function obtenerSexoDeCurp(string $curp): ?string
    {
        if (mb_strlen($curp) !== 18) {
            return null;
        }

        $sexo = mb_substr($curp, 10, 1);

        if (in_array($sexo, ['H', 'M'], true)) {
            return $sexo;
        }

        return null;
    }

    /**
     * Intenta obtener la fecha de nacimiento desde la CURP.
     */
    protected function obtenerFechaNacimientoDesdeCurp(string $curp): ?string
    {
        if (mb_strlen($curp) !== 18) {
            return null;
        }

        $anio = mb_substr($curp, 4, 2);
        $mes = mb_substr($curp, 6, 2);
        $dia = mb_substr($curp, 8, 2);

        if (!ctype_digit($anio) || !ctype_digit($mes) || !ctype_digit($dia)) {
            return null;
        }

        $anioCompleto = ((int) $anio <= 30) ? '20' . $anio : '19' . $anio;

        if (!checkdate((int) $mes, (int) $dia, (int) $anioCompleto)) {
            return null;
        }

        return "{$anioCompleto}-{$mes}-{$dia}";
    }

    /**
     * Genera datos falsos cuando se trabaja en modo pruebas.
     */
    protected function fakeResponse(string $curp): array
    {
        $seed = abs(crc32($curp));
        mt_srand($seed);

        $nombresHombre = [
            'CARLOS',
            'ALBERTO',
            'JUAN',
            'PEDRO',
            'ANGEL',
            'DANIEL',
            'MIGUEL',
            'JOSE',
            'LUIS',
            'FERNANDO',
        ];

        $nombresMujer = [
            'MARIA',
            'MELISA',
            'PAOLA',
            'YULISA',
            'KARLA',
            'ANDREA',
            'SOFIA',
            'DANIELA',
            'FERNANDA',
            'VALERIA',
        ];

        $apellidos = [
            'NUNEZ',
            'PEREZ',
            'GARCIA',
            'HERNANDEZ',
            'LOPEZ',
            'MARTINEZ',
            'SANCHEZ',
            'RAMIREZ',
            'FLORES',
            'TORRES',
        ];

        $claveSexo = $this->obtenerSexoDeCurp($curp) ?? ((mt_rand(0, 1) === 0) ? 'H' : 'M');

        $listaNombres = $claveSexo === 'H'
            ? $nombresHombre
            : $nombresMujer;

        $nombre1 = $listaNombres[mt_rand(0, count($listaNombres) - 1)];
        $nombre2 = $listaNombres[mt_rand(0, count($listaNombres) - 1)];

        while ($nombre2 === $nombre1) {
            $nombre2 = $listaNombres[mt_rand(0, count($listaNombres) - 1)];
        }

        $apellidoPaterno = $apellidos[mt_rand(0, count($apellidos) - 1)];
        $apellidoMaterno = $apellidos[mt_rand(0, count($apellidos) - 1)];

        $fechaNacimiento = $this->obtenerFechaNacimientoDesdeCurp($curp) ?? '2000-01-01';

        return [
            'error' => false,
            'code_error' => 0,
            'error_message' => '',
            'response' => [
                'Solicitante' => [
                    'CURP' => $curp,
                    'Nombres' => "{$nombre1} {$nombre2}",
                    'ApellidoPaterno' => $apellidoPaterno,
                    'ApellidoMaterno' => $apellidoMaterno,
                    'ClaveSexo' => $claveSexo,
                    'Sexo' => $claveSexo === 'H' ? 'Hombre' : 'Mujer',
                    'FechaNacimiento' => $fechaNacimiento,
                    'Nacionalidad' => 'MEX',
                    'ClaveEntidadNacimiento' => 'GR',
                    'EntidadNacimiento' => 'Guerrero',
                ],
            ],
        ];
    }
}
