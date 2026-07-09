<?php

namespace App\Services;

use App\Models\PersonaNivelDetalle;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CargaLaboralPersonaNivelService
{
    /**
     * Carga correspondiente únicamente a una función/asignación.
     *
     * @return array{horas_automaticas:float,ajuste:float,horas_frente_grupo:float,horas_administrativas:float,total:float,limite:float,sobrecarga:bool}
     */
    public function calcular(PersonaNivelDetalle $detalle): array
    {
        $detalle->loadMissing(['cabecera', 'asignacionMateria.horarios.hora']);

        $minutos = 0;
        foreach ($detalle->asignacionMateria?->horarios ?? [] as $horario) {
            if (!$horario->hora?->hora_inicio || !$horario->hora?->hora_fin) {
                continue;
            }

            $inicio = Carbon::createFromFormat('H:i:s', (string) $horario->hora->hora_inicio);
            $fin = Carbon::createFromFormat('H:i:s', (string) $horario->hora->hora_fin);
            $minutos += max(0, $inicio->diffInMinutes($fin, false));
        }

        $horasAutomaticas = round($minutos / 60, 2);
        $ajuste = (float) ($detalle->ajuste_horas_frente_grupo ?? 0);
        $horasFrenteGrupo = max(0, round($horasAutomaticas + $ajuste, 2));
        $horasAdministrativas = round((float) ($detalle->horas_administrativas ?? 0), 2);
        $total = round($horasFrenteGrupo + $horasAdministrativas, 2);
        $limite = (float) ($detalle->limite_horas_semanales ?? $detalle->cabecera?->limite_horas_semanales ?? 40);

        return [
            'horas_automaticas' => $horasAutomaticas,
            'ajuste' => $ajuste,
            'horas_frente_grupo' => $horasFrenteGrupo,
            'horas_administrativas' => $horasAdministrativas,
            'total' => $total,
            'limite' => $limite,
            'sobrecarga' => $total > $limite,
        ];
    }

    /**
     * Suma la carga completa de una persona dentro de un nivel. Las horas
     * administrativas generales de la cabecera se contabilizan una sola vez.
     *
     * @param Collection<int, PersonaNivelDetalle> $detalles
     * @return array{horas_frente_grupo:float,horas_administrativas_detalle:float,horas_administrativas_generales:float,horas_administrativas:float,total:float,limite:float,sobrecarga:bool}
     */
    public function calcularCabecera(Collection $detalles): array
    {
        $primero = $detalles->first();
        $cargas = $detalles->map(fn (PersonaNivelDetalle $detalle) => $this->calcular($detalle));

        $frenteGrupo = round((float) $cargas->sum('horas_frente_grupo'), 2);
        $adminDetalle = round((float) $cargas->sum('horas_administrativas'), 2);
        $adminGeneral = round((float) ($primero?->cabecera?->horas_administrativas ?? 0), 2);
        $adminTotal = round($adminDetalle + $adminGeneral, 2);
        $total = round($frenteGrupo + $adminTotal, 2);
        $limite = (float) ($primero?->cabecera?->limite_horas_semanales ?? 40);

        return [
            'horas_frente_grupo' => $frenteGrupo,
            'horas_administrativas_detalle' => $adminDetalle,
            'horas_administrativas_generales' => $adminGeneral,
            'horas_administrativas' => $adminTotal,
            'total' => $total,
            'limite' => $limite,
            'sobrecarga' => $total > $limite,
        ];
    }
}
