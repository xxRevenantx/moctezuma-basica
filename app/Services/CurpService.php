<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class CurpService
{
    public function esModoPruebas(): bool
    {
        return (string) config('curp.token') === 'pruebas';
    }

    public function estaConfigurado(): bool
    {
        return filled(config('curp.token'));
    }

    /**
     * Consulta datos externos de una CURP. Este método nunca decide si debe
     * consultarse: el componente debe comprobar primero la base local.
     *
     * @return array<string, mixed>
     */
    public function obtenerDatosPorCurp(string $curp): array
    {
        $curp = mb_strtoupper(trim($curp));

        if (mb_strlen($curp) !== 18 || ! preg_match('/^[A-Z0-9]{18}$/', $curp)) {
            return $this->respuestaError('Formato de CURP inválido.', 'formato');
        }

        if (! $this->estaConfigurado()) {
            return $this->respuestaError(
                'El servicio externo de CURP no está configurado. Captura los datos manualmente o configura CURP_API_TOKEN.',
                'configuracion'
            );
        }

        if ($this->esModoPruebas()) {
            return $this->normalizarRespuesta($this->fakeResponse($curp));
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout((int) config('curp.connect_timeout', 5))
                ->timeout((int) config('curp.timeout', 15))
                ->retry(2, 250, throw: false)
                ->get((string) config('curp.base_url'), [
                    'token' => (string) config('curp.token'),
                    'curp' => $curp,
                ]);

            if (! $response->successful()) {
                return $this->respuestaError(
                    'No se pudo consultar la CURP. Intenta nuevamente.',
                    'proveedor',
                    $response->status()
                );
            }

            return $this->normalizarRespuesta($response->json() ?? []);
        } catch (ConnectionException) {
            return $this->respuestaError('Error de conexión al consultar la CURP.', 'conexion');
        } catch (\Throwable) {
            return $this->respuestaError('Ocurrió un error al consultar la CURP.', 'inesperado');
        }
    }

    /** @return array<string, mixed> */
    protected function normalizarRespuesta(array $respuesta): array
    {
        if (($respuesta['error'] ?? true) === true) {
            return $this->respuestaError(
                $respuesta['error_message']
                    ?? $respuesta['message']
                    ?? 'No se encontraron datos para la CURP.',
                'no_encontrada'
            );
        }

        $solicitante = data_get($respuesta, 'response.Solicitante');

        if (! is_array($solicitante)) {
            return $this->respuestaError('La respuesta no contiene datos del solicitante.', 'respuesta_invalida');
        }

        $curp = trim((string) data_get($solicitante, 'CURP', ''));
        $nombres = trim((string) data_get($solicitante, 'Nombres', ''));
        $apellidoPaterno = trim((string) data_get($solicitante, 'ApellidoPaterno', ''));
        $apellidoMaterno = trim((string) data_get($solicitante, 'ApellidoMaterno', ''));
        $claveSexo = mb_strtoupper(trim((string) data_get($solicitante, 'ClaveSexo', '')));
        $sexo = trim((string) data_get($solicitante, 'Sexo', ''));
        $fechaNacimiento = $this->normalizarFechaNacimiento((string) data_get($solicitante, 'FechaNacimiento', ''));

        return [
            'error' => false,
            'message' => 'Datos encontrados correctamente.',
            'tipo_error' => null,
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
        ];
    }


    private function normalizarFechaNacimiento(string $fecha): string
    {
        $fecha = trim($fecha);

        if ($fecha === '') {
            return '';
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $formato) {
            $valor = \DateTimeImmutable::createFromFormat('!' . $formato, $fecha);
            $errores = \DateTimeImmutable::getLastErrors();

            if ($valor && ($errores === false || ($errores['warning_count'] === 0 && $errores['error_count'] === 0))) {
                return $valor->format('Y-m-d');
            }
        }

        return '';
    }

    /** @return array<string, mixed> */
    private function respuestaError(string $mensaje, string $tipo, ?int $status = null): array
    {
        return [
            'error' => true,
            'message' => $mensaje,
            'tipo_error' => $tipo,
            'status' => $status,
            'response' => null,
            'datos' => null,
        ];
    }

    protected function obtenerSexoDeCurp(string $curp): ?string
    {
        if (mb_strlen($curp) !== 18) {
            return null;
        }

        $sexo = mb_substr($curp, 10, 1);

        return in_array($sexo, ['H', 'M'], true) ? $sexo : null;
    }

    protected function obtenerFechaNacimientoDesdeCurp(string $curp): ?string
    {
        if (mb_strlen($curp) !== 18) {
            return null;
        }

        $anio = mb_substr($curp, 4, 2);
        $mes = mb_substr($curp, 6, 2);
        $dia = mb_substr($curp, 8, 2);
        $homoclave = mb_substr($curp, 16, 1);

        if (! ctype_digit($anio) || ! ctype_digit($mes) || ! ctype_digit($dia)) {
            return null;
        }

        $anioCompleto = (ctype_digit($homoclave) ? 1900 : 2000) + (int) $anio;

        if (! checkdate((int) $mes, (int) $dia, $anioCompleto)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $anioCompleto, (int) $mes, (int) $dia);
    }

    /** @return array<string, mixed> */
    protected function fakeResponse(string $curp): array
    {
        $seed = abs(crc32($curp));
        mt_srand($seed);

        $nombresHombre = ['CARLOS', 'ALBERTO', 'JUAN', 'PEDRO', 'ANGEL', 'DANIEL', 'MIGUEL', 'JOSE', 'LUIS', 'FERNANDO'];
        $nombresMujer = ['MARIA', 'MELISA', 'PAOLA', 'YULISA', 'KARLA', 'ANDREA', 'SOFIA', 'DANIELA', 'FERNANDA', 'VALERIA'];
        $apellidos = ['NUNEZ', 'PEREZ', 'GARCIA', 'HERNANDEZ', 'LOPEZ', 'MARTINEZ', 'SANCHEZ', 'RAMIREZ', 'FLORES', 'TORRES'];

        $claveSexo = $this->obtenerSexoDeCurp($curp) ?? (mt_rand(0, 1) === 0 ? 'H' : 'M');
        $listaNombres = $claveSexo === 'H' ? $nombresHombre : $nombresMujer;
        $nombre1 = $listaNombres[mt_rand(0, count($listaNombres) - 1)];
        $nombre2 = $listaNombres[mt_rand(0, count($listaNombres) - 1)];

        while ($nombre2 === $nombre1) {
            $nombre2 = $listaNombres[mt_rand(0, count($listaNombres) - 1)];
        }

        return [
            'error' => false,
            'response' => [
                'Solicitante' => [
                    'CURP' => $curp,
                    'Nombres' => "{$nombre1} {$nombre2}",
                    'ApellidoPaterno' => $apellidos[mt_rand(0, count($apellidos) - 1)],
                    'ApellidoMaterno' => $apellidos[mt_rand(0, count($apellidos) - 1)],
                    'ClaveSexo' => $claveSexo,
                    'Sexo' => $claveSexo === 'H' ? 'Hombre' : 'Mujer',
                    'FechaNacimiento' => $this->obtenerFechaNacimientoDesdeCurp($curp) ?? '2000-01-01',
                    'Nacionalidad' => 'MEX',
                    'ClaveEntidadNacimiento' => 'GR',
                    'EntidadNacimiento' => 'Guerrero',
                ],
            ],
        ];
    }
}
