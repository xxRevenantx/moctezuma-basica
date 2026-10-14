<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Calendario real de ocho semanas lectivas consecutivas (lunes a viernes).
 * Los resúmenes se separan por mes calendario, no por bloques arbitrarios:
 * ocho semanas pueden alcanzar hasta tres meses diferentes.
 */
class AsistenciaBimestralService
{
    public function construir(string $fechaSeleccionada): array
    {
        $inicio = CarbonImmutable::createFromFormat('!Y-m-d', $fechaSeleccionada)
            ->startOfWeek(CarbonInterface::MONDAY);

        $semanas = [];
        $meses = [];
        $diasTexto = ['L', 'M', 'M', 'J', 'V'];

        for ($numeroSemana = 0; $numeroSemana < 8; $numeroSemana++) {
            $dias = [];

            for ($dia = 0; $dia < 5; $dia++) {
                $fecha = $inicio->addWeeks($numeroSemana)->addDays($dia);
                $claveMes = $fecha->format('Y-m');

                if (!isset($meses[$claveMes])) {
                    $meses[$claveMes] = [
                        'clave' => $claveMes,
                        'nombre' => mb_strtoupper($fecha->locale('es')->translatedFormat('M Y'), 'UTF-8'),
                        'dias' => 0,
                    ];
                }

                $meses[$claveMes]['dias']++;
                $dias[] = [
                    'fecha' => $fecha->toDateString(),
                    'dia' => $diasTexto[$dia],
                    'numero' => $fecha->format('d'),
                    'mes' => $claveMes,
                    'mesCorto' => mb_strtoupper($fecha->locale('es')->translatedFormat('M'), 'UTF-8'),
                ];
            }

            $semanas[] = [
                'numero' => $numeroSemana + 1,
                'dias' => $dias,
                'inicio' => $dias[0]['fecha'],
                'fin' => $dias[4]['fecha'],
            ];
        }

        $fin = $inicio->addWeeks(7)->addDays(4);

        return [
            'inicio' => $inicio->toDateString(),
            'fin' => $fin->toDateString(),
            'inicioTexto' => $inicio->locale('es')->translatedFormat('d/m/Y'),
            'finTexto' => $fin->locale('es')->translatedFormat('d/m/Y'),
            'semanas' => $semanas,
            'meses' => array_values($meses),
            'totalDias' => 40,
        ];
    }
}
