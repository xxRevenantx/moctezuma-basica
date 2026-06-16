<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use App\Models\Nivel;
use App\Models\Persona;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class ProfesorHorarioPdfController extends Controller
{
    public function __invoke(Persona $profesor, string $nivel = 'todos')
    {
        $nivelId = $nivel !== 'todos' ? (int) $nivel : null;

        $horarios = Horario::query()
            ->with([
                'nivel:id,nombre,color,cct',
                'grado:id,nombre,orden',
                'generacion:id,anio_ingreso,anio_egreso,status',
                'semestre:id,numero,grado_id',
                'grupo:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'grupo.asignacionGrupo:id,nombre',
                'dia:id,nivel_id,dia,orden',
                'hora:id,nivel_id,hora_inicio,hora_fin,orden',
                'asignacionMateria:id,materia_id,grupo_id,profesor_id,orden',
                'asignacionMateria.materia:id,materia,nivel_id,grado_id,semestre_id,extra,receso,orden',
            ])
            ->whereHas('asignacionMateria', function ($query) use ($profesor) {
                $query->where('profesor_id', $profesor->id);
            })
            ->when($nivelId, function ($query) use ($nivelId) {
                $query->where('nivel_id', $nivelId);
            })
            ->get()
            ->sortBy([
                fn($a, $b) => ($a->nivel->id ?? 0) <=> ($b->nivel->id ?? 0),
                fn($a, $b) => ($a->dia->orden ?? 0) <=> ($b->dia->orden ?? 0),
                fn($a, $b) => ($a->hora->orden ?? 0) <=> ($b->hora->orden ?? 0),
            ])
            ->values();

        $matriz = $this->crearMatriz($horarios);

        $nivelSeleccionado = $nivelId
            ? Nivel::query()->select('id', 'nombre', 'cct')->find($nivelId)
            : null;

        $profesorNombre = trim(
            ($profesor->titulo ? $profesor->titulo . ' ' : '') .
                $profesor->nombre . ' ' .
                $profesor->apellido_paterno . ' ' .
                ($profesor->apellido_materno ?? '')
        );

        $pdf = Pdf::loadView('pdf.profesor-horario', [
            'profesor' => $profesor,
            'profesorNombre' => $profesorNombre,
            'nivelSeleccionado' => $nivelSeleccionado,
            'horarios' => $horarios,
            'matriz' => $matriz,
        ])->setPaper('letter', 'landscape');

        $nombreArchivo = 'horario-profesor-' . str($profesorNombre)
            ->lower()
            ->ascii()
            ->replace(' ', '-')
            ->replaceMatches('/[^a-z0-9\-]/', '')
            ->toString() . '.pdf';

        return $pdf->stream($nombreArchivo);
    }

    private function crearMatriz(Collection $horarios): Collection
    {
        return $horarios
            ->groupBy('nivel_id')
            ->map(function (Collection $items) {
                $nivel = $items->first()->nivel;

                $dias = $items
                    ->pluck('dia')
                    ->filter()
                    ->unique('id')
                    ->sortBy('orden')
                    ->values();

                $horas = $items
                    ->pluck('hora')
                    ->filter()
                    ->unique('id')
                    ->sortBy('orden')
                    ->values();

                $celdas = [];

                foreach ($items as $horario) {
                    $celdas[$horario->hora_id][$horario->dia_id][] = $horario;
                }

                return [
                    'nivel' => $nivel,
                    'dias' => $dias,
                    'horas' => $horas,
                    'celdas' => $celdas,
                    'total_clases' => $items->count(),
                    'total_materias' => $items->pluck('asignacion_materia_id')->unique()->count(),
                    'total_grupos' => $items->pluck('grupo_id')->unique()->count(),
                ];
            })
            ->values();
    }
}
