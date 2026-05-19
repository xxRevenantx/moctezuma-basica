<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use App\Models\Inscripcion;
use App\Models\Persona;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProfesorPdfController extends Controller
{
    public function asistencia(Request $request)
    {
        return $this->generarPdf($request, 'asistencia');
    }

    public function evaluacion(Request $request)
    {
        return $this->generarPdf($request, 'evaluacion');
    }

    private function generarPdf(Request $request, string $tipo)
    {
        $profesorId = (int) $request->query('profesor_id');
        $asignacionMateriaId = $request->query('asignacion_materia_id', 'todas');

        $profesor = Persona::query()
            ->with(['personaRoles.rolePersona:id,nombre,slug,status'])
            ->findOrFail($profesorId);

        $asignaciones = collect($request->query('asignaciones', []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $horarios = $this->obtenerHorarios($profesorId, $asignacionMateriaId, $asignaciones);

        if ($horarios->isEmpty()) {
            abort(404, 'No se encontraron materias en horario para generar el PDF.');
        }

        if ($asignacionMateriaId === 'todas') {
            $periodosPorMateria = $request->query('periodos', []);
            $parcialesPorMateria = $request->query('parciales', []);
        } else {
            $horario = $horarios->first();
            $asignacionId = (int) $horario->asignacion_materia_id;

            $periodosPorMateria = [];
            $parcialesPorMateria = [];

            if ($this->esBachillerato($horario)) {
                $parcialesPorMateria[$asignacionId] = $request->query('parcial_id');
            } else {
                $periodosPorMateria[$asignacionId] = $request->query('periodo_id');
            }
        }

        $bloques = $this->crearBloques($horarios, $periodosPorMateria, $parcialesPorMateria);

        $vista = $tipo === 'asistencia'
            ? 'pdf.lista_asistencia_pdf'
            : 'pdf.lista_evaluacion_pdf';

        $nombreArchivo = $tipo === 'asistencia'
            ? 'lista-asistencia-profesor.pdf'
            : 'lista-evaluacion-profesor.pdf';

        $pdf = Pdf::loadView($vista, [
            'profesor' => $profesor,
            'bloques' => $bloques,
            'fecha' => now()->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y'),
        ])->setPaper('letter', 'portrait');

        return $pdf->stream($nombreArchivo);
    }

    private function obtenerHorarios(int $profesorId, string $asignacionMateriaId, Collection $asignaciones): Collection
    {
        return Horario::query()
            ->with([
                'nivel:id,nombre,slug,cct,logo,director_id',
                'nivel.director:id,titulo,nombre,apellido_paterno,apellido_materno,cargo,status',
                'grado:id,nombre,orden',
                'generacion:id,anio_ingreso,anio_egreso',
                'semestre:id,grado_id,numero,orden_global',
                'grupo:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'grupo.asignacionGrupo:id,nombre',
                'dia:id,dia,orden',
                'hora:id,hora_inicio,hora_fin,orden',
                'asignacionMateria:id,materia_id,grupo_id,profesor_id,orden',
                'asignacionMateria.materia:id,nivel_id,grado_id,semestre_id,materia,clave,calificable,extra,receso,orden',
            ])
            ->whereHas('asignacionMateria', function ($consulta) use ($profesorId, $asignacionMateriaId, $asignaciones) {
                $consulta->where('profesor_id', $profesorId);

                if ($asignacionMateriaId !== 'todas') {
                    $consulta->where('id', (int) $asignacionMateriaId);
                }

                if ($asignacionMateriaId === 'todas' && $asignaciones->isNotEmpty()) {
                    $consulta->whereIn('id', $asignaciones->all());
                }
            })
            ->join('dias', 'dias.id', '=', 'horarios.dia_id')
            ->join('horas', 'horas.id', '=', 'horarios.hora_id')
            ->join('asignacion_materias', 'asignacion_materias.id', '=', 'horarios.asignacion_materia_id')
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->select('horarios.*')
            ->orderBy('materias.orden')
            ->orderBy('dias.orden')
            ->orderBy('horas.orden')
            ->get();
    }

    private function crearBloques(Collection $horarios, array $periodosPorMateria = [], array $parcialesPorMateria = []): Collection
    {
        return $horarios
            ->groupBy('asignacion_materia_id')
            ->map(function ($items) use ($periodosPorMateria, $parcialesPorMateria) {
                $horario = $items->first();
                $asignacionId = (int) $horario->asignacion_materia_id;

                $esBachillerato = $this->esBachillerato($horario);

                $periodo = null;
                $parcial = null;

                if ($esBachillerato) {
                    $periodoSeleccionado = $parcialesPorMateria[$asignacionId] ?? null;

                    $parcial = $this->obtenerPeriodoBachillerato($periodoSeleccionado);
                    $periodo = $parcial;
                } else {
                    $periodoSeleccionado = $periodosPorMateria[$asignacionId] ?? null;

                    $periodo = $this->obtenerPeriodoBasica($periodoSeleccionado);
                }

                if (!$periodo && !$parcial) {
                    abort(422, 'Hay materias sin periodo o parcial seleccionado.');
                }

                $alumnos = Inscripcion::query()
                    ->where('activo', 1)
                    ->where('nivel_id', $horario->nivel_id)
                    ->where('grado_id', $horario->grado_id)
                    ->where('generacion_id', $horario->generacion_id)
                    ->where('grupo_id', $horario->grupo_id)
                    ->when($horario->semestre_id, function ($consulta) use ($horario) {
                        $consulta->where(function ($q) use ($horario) {
                            $q->where('semestre_id', $horario->semestre_id)
                                ->orWhereNull('semestre_id');
                        });
                    })
                    ->orderBy('apellido_paterno')
                    ->orderBy('apellido_materno')
                    ->orderBy('nombre')
                    ->get();

                return [
                    'horario_base' => $horario,
                    'horarios' => $items->values(),
                    'alumnos' => $alumnos,
                    'periodo' => $periodo,
                    'parcial' => $parcial,
                    'es_bachillerato' => $esBachillerato,
                ];
            })
            ->values();
    }

    private function obtenerPeriodoBasica(?string $periodoId): ?object
    {
        if (!$periodoId) {
            return null;
        }

        return DB::table('periodos')
            ->leftJoin('periodos_basica', 'periodos_basica.id', '=', 'periodos.periodo_basica_id')
            ->leftJoin('meses_basica', 'meses_basica.id', '=', 'periodos.mes_basica_id')
            ->leftJoin('ciclo_escolares', 'ciclo_escolares.id', '=', 'periodos.ciclo_escolar_id')
            ->where('periodos.id', $periodoId)
            ->whereNotNull('periodos.periodo_basica_id')
            ->select(
                'periodos.*',
                'periodos_basica.periodo',
                'periodos_basica.descripcion',
                'meses_basica.meses',
                'meses_basica.meses_corto',
                'ciclo_escolares.inicio_anio',
                'ciclo_escolares.fin_anio'
            )
            ->first();
    }

    private function obtenerPeriodoBachillerato(?string $periodoId): ?object
    {
        if (!$periodoId) {
            return null;
        }

        return DB::table('periodos')
            ->leftJoin('parciales', 'parciales.id', '=', 'periodos.parcial_bachillerato_id')
            ->leftJoin('meses_bachilleratos', 'meses_bachilleratos.id', '=', 'periodos.mes_bachillerato_id')
            ->leftJoin('ciclo_escolares', 'ciclo_escolares.id', '=', 'periodos.ciclo_escolar_id')
            ->where('periodos.id', $periodoId)
            ->whereNotNull('periodos.parcial_bachillerato_id')
            ->select(
                'periodos.*',
                'parciales.parcial',
                'parciales.descripcion',
                'meses_bachilleratos.meses',
                'meses_bachilleratos.meses_corto',
                'ciclo_escolares.inicio_anio',
                'ciclo_escolares.fin_anio'
            )
            ->first();
    }

    private function esBachillerato($horario): bool
    {
        return (int) $horario->nivel_id === 4 || $horario->nivel?->slug === 'bachillerato';
    }
}
