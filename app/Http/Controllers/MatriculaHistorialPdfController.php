<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use App\Models\Nivel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;

class MatriculaHistorialPdfController extends Controller
{
    public function __invoke(Request $request, string $slug_nivel)
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $nivel = Nivel::query()
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $cicloEscolarId = $request->integer('ciclo_escolar_id');

        if ($cicloEscolarId) {
            $historico = InscripcionCiclo::query()
                ->with([
                    'inscripcion' => fn ($q) => $q->withTrashed(),
                    'generacion',
                    'grado',
                    'semestre',
                    'grupo.asignacionGrupo',
                ])
                ->where('ciclo_escolar_id', $cicloEscolarId)
                ->where('nivel_id', $nivel->id)
                ->when($request->integer('generacion_id'), fn ($q, $id) => $q->where('generacion_id', $id))
                ->when($request->integer('grado_id'), fn ($q, $id) => $q->where('grado_id', $id))
                ->when($request->integer('semestre_id'), fn ($q, $id) => $q->where('semestre_id', $id))
                ->when($request->integer('grupo_id'), fn ($q, $id) => $q->where('grupo_id', $id))
                ->when($request->input('estatus', 'todos') !== 'todos', function ($q) use ($request): void {
                    $estatus = (string) $request->input('estatus');
                    $q->where(function ($sub) use ($estatus): void {
                        $sub->where('resultado_final', $estatus)
                            ->orWhere('estatus_actual_ciclo', $estatus);
                    });
                })
                ->orderBy('id')
                ->get()
                ->filter(fn (InscripcionCiclo $ciclo) => $request->boolean('mostrar_archivados') || ! $ciclo->inscripcion?->trashed())
                ->filter(function (InscripcionCiclo $ciclo) use ($request): bool {
                    $termino = mb_strtolower(trim((string) $request->input('search')));
                    if ($termino === '') return true;
                    $alumno = $ciclo->inscripcion;
                    $texto = mb_strtolower(implode(' ', [
                        $ciclo->matricula,
                        $alumno?->curp,
                        $alumno?->nombre,
                        $alumno?->apellido_paterno,
                        $alumno?->apellido_materno,
                    ]));
                    return str_contains($texto, $termino);
                });

            $rows = $historico->map(function (InscripcionCiclo $ciclo): Fluent {
                $alumno = $ciclo->inscripcion;
                return new Fluent([
                    'id' => $ciclo->inscripcion_id,
                    'matricula' => $ciclo->matricula ?: $alumno?->matricula,
                    'curp' => $alumno?->curp,
                    'nombre' => $alumno?->nombre,
                    'apellido_paterno' => $alumno?->apellido_paterno,
                    'apellido_materno' => $alumno?->apellido_materno,
                    'genero' => $alumno?->genero,
                    'estatus' => $ciclo->resultado_final ?: $ciclo->estatus_actual_ciclo,
                    'fecha_inscripcion' => $ciclo->fecha_ingreso,
                    'generacion' => $ciclo->generacion,
                    'grado' => $ciclo->grado,
                    'semestre' => $ciclo->semestre,
                    'grupo' => $ciclo->grupo,
                    'archivado' => (bool) $alumno?->trashed(),
                ]);
            })->sortBy(fn (Fluent $fila) => mb_strtolower(trim($fila->apellido_paterno.' '.$fila->apellido_materno.' '.$fila->nombre)))->values();
        } else {
            $query = $request->boolean('mostrar_archivados')
                ? Inscripcion::withTrashed()
                : Inscripcion::query();

            $query->with(['generacion', 'grado', 'semestre', 'grupo.asignacionGrupo'])
                ->where('nivel_id', $nivel->id)
                ->when($request->integer('generacion_id'), fn ($q, $id) => $q->where('generacion_id', $id), fn ($q) => $q->whereHas('generacion', fn ($g) => $g->where('status', true)))
                ->when($request->integer('grado_id'), fn ($q, $id) => $q->where('grado_id', $id))
                ->when($request->integer('semestre_id'), fn ($q, $id) => $q->where('semestre_id', $id))
                ->when($request->integer('grupo_id'), fn ($q, $id) => $q->where('grupo_id', $id))
                ->when($request->input('estatus', 'todos') !== 'todos', fn ($q) => $q->where('estatus', $request->input('estatus')))
                ->when(trim((string) $request->input('search')) !== '', function ($q) use ($request): void {
                    $termino = '%'.trim((string) $request->input('search')).'%';
                    $q->where(function ($subquery) use ($termino): void {
                        $subquery->where('matricula', 'like', $termino)
                            ->orWhere('curp', 'like', $termino)
                            ->orWhere('nombre', 'like', $termino)
                            ->orWhere('apellido_paterno', 'like', $termino)
                            ->orWhere('apellido_materno', 'like', $termino);
                    });
                })
                ->orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombre');

            $rows = $query->get();
        }

        $generaciones = $rows
            ->pluck('generacion')
            ->filter()
            ->unique('id')
            ->sortBy([
                ['anio_ingreso', 'asc'],
                ['anio_egreso', 'asc'],
            ])
            ->values();

        $etiquetasGeneracion = $generaciones
            ->map(fn($generacion) => $generacion->etiqueta)
            ->filter()
            ->values();

        $tituloDocumento = 'Matrícula - ' . (
            $etiquetasGeneracion->isNotEmpty()
            ? $etiquetasGeneracion->map(fn($etiqueta) => 'Gen: ' . $etiqueta)->implode(' | ')
            : 'Sin generación'
        );

        $resumen = [
            'total' => $rows->count(),
            'hombres' => $rows->where('genero', 'H')->count(),
            'mujeres' => $rows->where('genero', 'M')->count(),
            'activos' => $rows->whereIn('estatus', ['activo', 'reingreso', 'no_promovido'])->count(),
            'egresados' => $rows->where('estatus', 'egresado')->count(),
            'bajas' => $rows->whereIn('estatus', ['baja', 'baja_temporal', 'baja_definitiva'])->count(),
        ];

        $filtros = [
            'ciclo_escolar_id' => $request->integer('ciclo_escolar_id'),
            'estatus' => $request->input('estatus', 'todos'),
            'mostrar_archivados' => $request->boolean('mostrar_archivados'),
            'busqueda' => trim((string) $request->input('search')),
        ];

        $nombreGeneracion = $etiquetasGeneracion->count() === 1
            ? Str::slug($etiquetasGeneracion->first())
            : 'varias-generaciones';

        $nombreArchivo = sprintf(
            'matricula-%s-%s.pdf',
            Str::slug($nivel->nombre),
            $nombreGeneracion
        );

        return Pdf::loadView('pdf.matricula-generaciones', compact(
            'rows',
            'nivel',
            'resumen',
            'generaciones',
            'etiquetasGeneracion',
            'tituloDocumento',
            'filtros'
        ))
            ->setPaper('letter', 'landscape')
            ->stream($nombreArchivo);
    }
}
