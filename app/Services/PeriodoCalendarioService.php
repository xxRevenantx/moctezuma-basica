<?php

namespace App\Services;

use App\Models\Periodos;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PeriodoCalendarioService
{
    public function traslapes(array $contexto, ?int $ignorarId = null): Collection
    {
        $inicio = $contexto['fecha_evaluacion_inicio'] ?? $contexto['fecha_inicio'] ?? null;
        $fin = $contexto['fecha_evaluacion_fin'] ?? $contexto['fecha_fin'] ?? null;

        if (!$inicio || !$fin) {
            return collect();
        }

        return Periodos::query()
            ->with(['periodoBasica', 'parcialBachillerato', 'semestre'])
            ->where('ciclo_escolar_id', $contexto['ciclo_escolar_id'])
            ->where('nivel_id', $contexto['nivel_id'])
            ->when($contexto['generacion_id'] ?? null, fn ($q, $id) => $q->where('generacion_id', $id))
            ->when($contexto['semestre_id'] ?? null, fn ($q, $id) => $q->where('semestre_id', $id))
            ->when($ignorarId, fn ($q) => $q->whereKeyNot($ignorarId))
            ->where(function ($q) use ($inicio, $fin) {
                $q->where(function ($dates) use ($inicio, $fin) {
                    $dates->whereNotNull('fecha_evaluacion_inicio')
                        ->whereNotNull('fecha_evaluacion_fin')
                        ->where('fecha_evaluacion_inicio', '<=', $fin)
                        ->where('fecha_evaluacion_fin', '>=', $inicio);
                })->orWhere(function ($dates) use ($inicio, $fin) {
                    $dates->whereNull('fecha_evaluacion_inicio')
                        ->whereNotNull('fecha_inicio')
                        ->whereNotNull('fecha_fin')
                        ->where('fecha_inicio', '<=', $fin)
                        ->where('fecha_fin', '>=', $inicio);
                });
            })
            ->get();
    }

    public function etiqueta(Periodos $periodo): string
    {
        $nombre = $periodo->periodoBasica?->descripcion
            ?? $periodo->parcialBachillerato?->descripcion
            ?? ('Periodo #' . $periodo->id);

        $inicio = $periodo->fecha_evaluacion_inicio ?? $periodo->fecha_inicio;
        $fin = $periodo->fecha_evaluacion_fin ?? $periodo->fecha_fin;

        return trim($nombre . ($inicio && $fin
            ? ' (' . Carbon::parse($inicio)->format('d/m/Y') . '–' . Carbon::parse($fin)->format('d/m/Y') . ')'
            : ''));
    }
}
