<?php

namespace App\Support\Documentos;

use Illuminate\Validation\ValidationException;

final class RangoPaginas
{
    /**
     * Convierte expresiones como "1-3,5,8-9" en una lista de páginas única y ordenada.
     *
     * @return array<int, int>
     */
    public static function interpretar(string $rango, int $maximo): array
    {
        $rango = trim($rango);

        if ($rango === '') {
            return [];
        }

        if ($maximo < 1) {
            throw ValidationException::withMessages([
                'rangos' => 'El archivo no tiene páginas disponibles para asignar.',
            ]);
        }

        $resultado = [];

        foreach (preg_split('/\s*,\s*/', $rango) ?: [] as $segmento) {
            $segmento = trim($segmento);

            if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $segmento, $coincidencias)) {
                $inicio = (int) $coincidencias[1];
                $fin = (int) $coincidencias[2];

                if ($inicio > $fin) {
                    [$inicio, $fin] = [$fin, $inicio];
                }

                foreach (range($inicio, $fin) as $pagina) {
                    $resultado[] = $pagina;
                }
            } elseif (ctype_digit($segmento)) {
                $resultado[] = (int) $segmento;
            } else {
                throw ValidationException::withMessages([
                    'rangos' => "El segmento '{$segmento}' no es válido.",
                ]);
            }
        }

        $resultado = array_values(array_unique($resultado));

        foreach ($resultado as $pagina) {
            if ($pagina < 1 || $pagina > $maximo) {
                throw ValidationException::withMessages([
                    'rangos' => "La página {$pagina} está fuera del rango 1-{$maximo}.",
                ]);
            }
        }

        sort($resultado);

        return $resultado;
    }
}
