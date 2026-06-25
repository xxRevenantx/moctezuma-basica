<?php

namespace App\Http\Controllers;

use App\Models\Ciclo;
use App\Models\CicloEscolar;
use App\Models\Nivel;
use App\Models\TrayectoriaAcademica;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MatriculaHistorialPdfController extends Controller
{
    public function __invoke(Request $request, string $slug_nivel)
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();
        $cicloEscolar = CicloEscolar::query()->findOrFail($request->integer('ciclo_escolar_id'));
        $corte = Ciclo::query()->findOrFail($request->integer('ciclo_id'));
        $mostrarArchivados = $request->boolean('mostrar_archivados');
        $estatus = (string) $request->input('estatus', 'todos');
        $busqueda = preg_replace('/\s+/', ' ', trim((string) $request->input('search', '')));

        $query = TrayectoriaAcademica::query()
            ->with([
                'inscripcion' => fn($q) => $q->withTrashed()->with('matriculasAlumno'),
                'nivel:id,nombre,slug',
                'grado:id,nombre,orden',
                'generacion:id,anio_ingreso,anio_egreso',
                'grupo.asignacionGrupo:id,nombre',
                'semestre:id,numero',
                'cicloEscolar:id,inicio_anio,fin_anio,es_actual,cerrado_at',
                'ciclo:id,ciclo',
            ])
            ->where('ciclo_escolar_id', $cicloEscolar->id)
            ->where('ciclo_id', $corte->id)
            ->where('nivel_id', $nivel->id)
            ->where('vigente_en_corte', true)
            ->when($request->integer('generacion_id'), fn(Builder $q, int $id) => $q->where('generacion_id', $id))
            ->when($request->integer('grado_id'), fn(Builder $q, int $id) => $q->where('grado_id', $id))
            ->when($request->integer('semestre_id'), fn(Builder $q, int $id) => $q->where('semestre_id', $id))
            ->when($request->integer('grupo_id'), fn(Builder $q, int $id) => $q->where('grupo_id', $id))
            ->when($estatus !== 'todos', fn(Builder $q) => $q->where('estatus', $estatus))
            ->whereHas('inscripcion', function (Builder $q) use ($mostrarArchivados, $busqueda) {
                if (!$mostrarArchivados) {
                    $q->whereNull('deleted_at');
                }

                if ($busqueda !== '') {
                    $like = "%{$busqueda}%";
                    $q->where(function (Builder $buscar) use ($like) {
                        $buscar->where('matricula', 'like', $like)
                            ->orWhere('folio', 'like', $like)
                            ->orWhere('curp', 'like', $like)
                            ->orWhere('nombre', 'like', $like)
                            ->orWhere('apellido_paterno', 'like', $like)
                            ->orWhere('apellido_materno', 'like', $like)
                            ->orWhereRaw("CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?", [$like])
                            ->orWhereHas('matriculasAlumno', fn(Builder $m) => $m->where('matricula', 'like', $like));
                    });
                }
            })
            ->join('inscripciones', 'inscripciones.id', '=', 'trayectorias_academicas.inscripcion_id')
            ->orderBy('inscripciones.apellido_paterno')
            ->orderBy('inscripciones.apellido_materno')
            ->orderBy('inscripciones.nombre')
            ->select('trayectorias_academicas.*');

        $rows = $query->get()->map(function (TrayectoriaAcademica $trayectoria) {
            $alumno = $trayectoria->inscripcion;
            $trayectoria->setAttribute(
                'matricula_contexto',
                $alumno?->matriculasAlumno?->firstWhere('nivel_id', $trayectoria->nivel_id)?->matricula
                    ?: $alumno?->matricula
                    ?: '—'
            );

            return $trayectoria;
        });

        $resumen = [
            'total' => $rows->count(),
            'hombres' => $rows->filter(fn($row) => $row->inscripcion?->genero === 'H')->count(),
            'mujeres' => $rows->filter(fn($row) => $row->inscripcion?->genero === 'M')->count(),
            'bajas' => $rows->whereIn('estatus', ['baja_temporal', 'baja_definitiva', 'traslado'])->count(),
        ];

        $pdf = Pdf::loadView('pdf.matricula-historica', compact(
            'rows',
            'nivel',
            'cicloEscolar',
            'corte',
            'resumen',
            'estatus',
            'busqueda'
        ))->setPaper('letter', 'landscape');

        return $pdf->download(
            'matricula_historica_' . $nivel->slug . '_' . $cicloEscolar->nombre . '_' . now()->format('Ymd_His') . '.pdf'
        );
    }
}
