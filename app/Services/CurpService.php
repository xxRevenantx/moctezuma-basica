<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CurpService
{
     // URL DE PRUEBAS
    // protected $baseUrl = 'https://api.valida-curp.com.mx/curp/obtener_datos/?token=pruebas&curp=XAXX010101XAXAXA00';

    protected $baseUrl = 'https://api.valida-curp.com.mx/curp/obtener_datos/?';

    protected $token = 'pruebas';
    // protected $token = '8d51c37a-87b1-40c9-8ae6-7b5651406d1f';

    public function obtenerDatosPorCurp(string $curp)
    {
        $response = Http::get($this->baseUrl, [
            'token' => $this->token,
            'curp' => $curp,
        ]);

        if ($response->successful()) {
            return $response->json(); // Devuelve los datos del CURP
        }

        return [
            'error' => true,
            'message' => 'CURP inválido o error de conexión',
            'status' => $response->status(),
        ];
    }
}
