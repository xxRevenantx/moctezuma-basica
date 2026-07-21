<?php

namespace App\Services;

use App\Models\CicloEscolarNivel;
use App\Models\PlantillaPersonalNivel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CicloNivelGateService
{
    public function diagnostico(int $cicloEscolarId, int $nivelId): array
    {
        $estado = CicloEscolarNivel::query()
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId)
            ->first();

        $grupos = DB::table('grupos')
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId)
            ->where('estado', 'activo')
            ->count();

        $periodos = DB::table('periodos')
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId)
            ->count();

        $plantilla = PlantillaPersonalNivel::query()
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId)
            ->first();

        $asignacionesActivas = DB::table('asignacion_materias')
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId)
            ->whereIn('estado', ['activa', 'cerrada'])
            ->count();

        return [
            'estado' => $estado?->estado ?? 'pendiente',
            'grupos' => $grupos,
            'periodos' => $periodos,
            'plantilla_publicada' => $plantilla?->disponibleParaDocumentos() ?? false,
            'asignaciones_activas' => $asignacionesActivas,
        ];
    }

    public function asegurar(int $cicloEscolarId, int $nivelId, string $modulo): void
    {
        $d = $this->diagnostico($cicloEscolarId, $nivelId);
        $faltantes = [];

        if ($d['grupos'] < 1) {
            $faltantes[] = 'grupos activos';
        }

        if (in_array($modulo, ['asignacion_materias', 'horarios', 'calificaciones', 'fichas'], true)
            && ! $d['plantilla_publicada']) {
            $faltantes[] = 'plantilla de personal publicada';
        }

        if (in_array($modulo, ['calificaciones', 'fichas'], true) && $d['periodos'] < 1) {
            $faltantes[] = 'periodos configurados';
        }

        if (in_array($modulo, ['horarios', 'calificaciones'], true) && $d['asignaciones_activas'] < 1) {
            $faltantes[] = 'asignaciones de materias activas';
        }

        if ($faltantes) {
            throw ValidationException::withMessages([
                'ciclo_escolar_id' => 'El nivel todavía no está listo para este módulo. Falta: ' . implode(', ', $faltantes) . '.',
            ]);
        }
    }
}
