<?php

namespace App\Http\Controllers;

use App\Models\AsignacionMateria;
use App\Models\cicloEscolar;
use App\Models\Escuela;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Materia;
use App\Models\Persona;
use App\Models\TallerSesion;
use App\Services\ListaAcademicaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProfesorPdfController extends Controller
{
    public function asistencia(Request $request, ListaAcademicaService $listas)
    {
        return $this->generarPdf($request, 'asistencia', $listas);
    }

    public function evaluacion(Request $request, ListaAcademicaService $listas)
    {
        return $this->generarPdf($request, 'evaluacion', $listas);
    }

    private function generarPdf(Request $request, string $tipo, ListaAcademicaService $listas)
    {
        $datos = $request->validate([
            'profesor_id' => ['required', 'integer', 'exists:personas,id'],
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'fecha_corte' => ['required', 'date'],
            'tipo_carga' => ['nullable', 'in:materia,taller'],
            'carga_id' => ['nullable', 'integer'],
            'grupo_id' => ['nullable', 'integer'],
            'periodo_id' => ['nullable', 'integer'],
            'cargas' => ['nullable', 'array'],
            'cargas.*' => ['string'],
            'periodos' => ['nullable', 'array'],
        ]);

        $ciclo = cicloEscolar::query()->findOrFail((int) $datos['ciclo_escolar_id']);
        $this->autorizarConsultaDelCiclo($ciclo);

        $profesor = Persona::query()
            ->with(['personaRoles.rolePersona:id,nombre,slug,status'])
            ->findOrFail((int) $datos['profesor_id']);

        $solicitudes = $this->normalizarSolicitudes($request);

        if ($solicitudes->isEmpty()) {
            abort(422, 'Selecciona al menos una carga académica para generar la lista.');
        }

        $periodos = (array) $request->query('periodos', []);
        $bloques = collect();

        foreach ($solicitudes as $solicitud) {
            $clave = $solicitud['clave'];
            $periodoId = $solicitud['periodo_id'] ?? ($periodos[$clave] ?? null);

            if (!$periodoId) {
                throw ValidationException::withMessages([
                    'periodo_id' => "Selecciona el periodo o parcial de la carga {$clave}.",
                ]);
            }

            if ($solicitud['tipo'] === 'materia') {
                $bloques->push($this->crearBloqueMateria(
                    profesorId: (int) $profesor->id,
                    cicloEscolarId: (int) $ciclo->id,
                    asignacionId: (int) $solicitud['carga_id'],
                    periodoId: (int) $periodoId,
                    fechaCorte: (string) $datos['fecha_corte'],
                    tipoPdf: $tipo,
                    listas: $listas,
                ));
                continue;
            }

            if ($tipo === 'evaluacion') {
                // Los talleres conjuntos y materias no calificables únicamente generan asistencia.
                continue;
            }

            $bloques->push($this->crearBloqueTaller(
                profesorId: (int) $profesor->id,
                cicloEscolarId: (int) $ciclo->id,
                sesionId: (int) $solicitud['carga_id'],
                grupoId: (int) $solicitud['grupo_id'],
                periodoId: (int) $periodoId,
                fechaCorte: (string) $datos['fecha_corte'],
                listas: $listas,
            ));
        }

        $bloques = $bloques->filter()->values();

        if ($bloques->isEmpty()) {
            abort(422, 'No hay cargas calificables o visibles para generar este PDF.');
        }

        $vista = $tipo === 'asistencia'
            ? 'pdf.lista_asistencia_pdf'
            : 'pdf.lista_evaluacion_pdf';

        $nombreArchivo = $tipo === 'asistencia'
            ? 'lista-asistencia-profesor-' . $ciclo->inicio_anio . '-' . $ciclo->fin_anio . '.pdf'
            : 'lista-evaluacion-profesor-' . $ciclo->inicio_anio . '-' . $ciclo->fin_anio . '.pdf';

        return Pdf::loadView($vista, [
            'profesor' => $profesor,
            'bloques' => $bloques,
            'escuela' => Escuela::query()->first(),
            'fecha' => now()->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y'),
            'fechaCorte' => $listas->fechaCorte($datos['fecha_corte'])->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y'),
            'cicloEscolar' => $ciclo,
        ])->setPaper('letter', 'portrait')->stream($nombreArchivo);
    }

    private function autorizarConsultaDelCiclo(cicloEscolar $ciclo): void
    {
        if (!$ciclo->es_actual) {
            abort_unless(auth()->user()?->is_admin, 403, 'Solo administración puede consultar listas históricas.');
        }
    }

    private function normalizarSolicitudes(Request $request): Collection
    {
        if ($request->filled('tipo_carga') && $request->filled('carga_id')) {
            $tipo = (string) $request->query('tipo_carga');
            $cargaId = (int) $request->query('carga_id');
            $grupoId = (int) $request->query('grupo_id');

            return collect([[
                'tipo' => $tipo,
                'carga_id' => $cargaId,
                'grupo_id' => $grupoId,
                'clave' => $tipo === 'materia' ? "m_{$cargaId}" : "t_{$cargaId}_{$grupoId}",
                'periodo_id' => $request->query('periodo_id'),
            ]]);
        }

        return collect($request->query('cargas', []))
            ->map(function ($valor) {
                $partes = explode(':', (string) $valor);
                $tipoCorto = $partes[0] ?? '';
                $cargaId = (int) ($partes[1] ?? 0);
                $grupoId = (int) ($partes[2] ?? 0);

                if (!in_array($tipoCorto, ['m', 't'], true) || !$cargaId) {
                    return null;
                }

                return [
                    'tipo' => $tipoCorto === 'm' ? 'materia' : 'taller',
                    'carga_id' => $cargaId,
                    'grupo_id' => $grupoId,
                    'clave' => $tipoCorto === 'm' ? "m_{$cargaId}" : "t_{$cargaId}_{$grupoId}",
                ];
            })
            ->filter()
            ->unique('clave')
            ->values();
    }

    private function crearBloqueMateria(
        int $profesorId,
        int $cicloEscolarId,
        int $asignacionId,
        int $periodoId,
        string $fechaCorte,
        string $tipoPdf,
        ListaAcademicaService $listas,
    ): array {
        $asignacion = AsignacionMateria::query()
            ->with([
                'profesor:id,titulo,nombre,apellido_paterno,apellido_materno',
                'materia:id,nivel_id,grado_id,semestre_id,materia,clave,slug,calificable,extra,receso,orden',
                'nivel:id,nombre,slug,cct,logo,director_id',
                'nivel.director:id,titulo,nombre,apellido_paterno,apellido_materno,cargo,status',
                'grado:id,nombre,orden',
                'generacion:id,anio_ingreso,anio_egreso',
                'semestre:id,grado_id,numero,orden_global',
                'grupo:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'grupo.asignacionGrupo:id,nombre',
                'grupo.nivel:id,nombre,slug,cct,logo,director_id',
                'grupo.grado:id,nombre,orden',
                'grupo.generacion:id,anio_ingreso,anio_egreso',
                'grupo.semestre:id,grado_id,numero,orden_global',
                'horarios' => fn ($q) => $q
                    ->where('ciclo_escolar_id', $cicloEscolarId)
                    ->with(['dia:id,dia,orden', 'hora:id,hora_inicio,hora_fin,orden'])
                    ->orderBy('dia_id')
                    ->orderBy('hora_id'),
            ])
            ->whereKey($asignacionId)
            ->where('profesor_id', $profesorId)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA)
            ->firstOrFail();

        abort_if((bool) $asignacion->materia?->receso, 422, 'El receso no genera listas.');

        if ($tipoPdf === 'evaluacion') {
            abort_unless((bool) $asignacion->materia?->calificable, 422, 'La materia seleccionada no es calificable.');
        }

        $periodo = $this->obtenerPeriodo($periodoId, $cicloEscolarId, $asignacion->nivel_id, $asignacion->generacion_id, $asignacion->semestre_id);
        $base = $this->horarioBaseDeAsignacion($asignacion);

        return [
            'tipo_carga' => 'materia',
            'carga' => $asignacion,
            'horario_base' => $base,
            'horarios' => $asignacion->horarios->values(),
            'alumnos' => $listas->alumnosDeAsignacion($asignacion, $fechaCorte),
            'periodo' => $periodo,
            'parcial' => $this->esBachillerato($asignacion->nivel_id, $asignacion->nivel?->slug) ? $periodo : null,
            'es_bachillerato' => $this->esBachillerato($asignacion->nivel_id, $asignacion->nivel?->slug),
            'fecha_corte' => $listas->fechaCorte($fechaCorte),
            'profesor_historico' => $asignacion->profesor,
        ];
    }

    private function crearBloqueTaller(
        int $profesorId,
        int $cicloEscolarId,
        int $sesionId,
        int $grupoId,
        int $periodoId,
        string $fechaCorte,
        ListaAcademicaService $listas,
    ): array {
        $sesion = TallerSesion::query()
            ->with([
                'profesor:id,titulo,nombre,apellido_paterno,apellido_materno',
                'taller.nivel:id,nombre,slug,cct,logo,director_id',
                'taller.nivel.director:id,titulo,nombre,apellido_paterno,apellido_materno,cargo,status',
                'dia:id,dia,orden',
                'hora:id,hora_inicio,hora_fin,orden',
                'grupos:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'grupos.asignacionGrupo:id,nombre',
                'grupos.nivel:id,nombre,slug,cct,logo,director_id',
                'grupos.grado:id,nombre,orden',
                'grupos.generacion:id,anio_ingreso,anio_egreso',
                'grupos.semestre:id,grado_id,numero,orden_global',
            ])
            ->whereKey($sesionId)
            ->where('profesor_id', $profesorId)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('estado', '!=', TallerSesion::ESTADO_ARCHIVADA)
            ->firstOrFail();

        $grupo = $sesion->grupos->firstWhere('id', $grupoId);
        abort_unless($grupo, 404, 'El grupo no pertenece a la sesión del taller.');

        $periodo = $this->obtenerPeriodo($periodoId, $cicloEscolarId, $grupo->nivel_id, $grupo->generacion_id, $grupo->semestre_id);
        $base = $this->horarioBaseDeTaller($sesion, $grupo);

        return [
            'tipo_carga' => 'taller',
            'carga' => $sesion,
            'horario_base' => $base,
            'horarios' => collect([$base]),
            'alumnos' => $listas->alumnosDeTaller($sesion, $grupo, $fechaCorte),
            'periodo' => $periodo,
            'parcial' => $this->esBachillerato($grupo->nivel_id, $grupo->nivel?->slug) ? $periodo : null,
            'es_bachillerato' => $this->esBachillerato($grupo->nivel_id, $grupo->nivel?->slug),
            'fecha_corte' => $listas->fechaCorte($fechaCorte),
            'profesor_historico' => $sesion->profesor,
        ];
    }

    private function obtenerPeriodo(
        int $periodoId,
        int $cicloEscolarId,
        ?int $nivelId,
        ?int $generacionId,
        ?int $semestreId,
    ): object {
        $query = DB::table('periodos')
            ->leftJoin('periodos_basica', 'periodos_basica.id', '=', 'periodos.periodo_basica_id')
            ->leftJoin('meses_basica', 'meses_basica.id', '=', 'periodos.mes_basica_id')
            ->leftJoin('parciales', 'parciales.id', '=', 'periodos.parcial_bachillerato_id')
            ->leftJoin('meses_bachilleratos', 'meses_bachilleratos.id', '=', 'periodos.mes_bachillerato_id')
            ->leftJoin('ciclo_escolares', 'ciclo_escolares.id', '=', 'periodos.ciclo_escolar_id')
            ->where('periodos.id', $periodoId)
            ->where('periodos.ciclo_escolar_id', $cicloEscolarId)
            ->when($nivelId, fn ($q) => $q->where('periodos.nivel_id', $nivelId))
            ->when((int) $nivelId === 4 && $generacionId, fn ($q) => $q->where('periodos.generacion_id', $generacionId))
            ->when((int) $nivelId === 4 && $semestreId, fn ($q) => $q->where('periodos.semestre_id', $semestreId))
            ->select(
                'periodos.*',
                'periodos_basica.periodo',
                DB::raw('COALESCE(periodos_basica.descripcion, parciales.descripcion) as descripcion'),
                DB::raw('COALESCE(meses_basica.meses, meses_bachilleratos.meses) as meses'),
                'parciales.parcial',
                'ciclo_escolares.inicio_anio',
                'ciclo_escolares.fin_anio'
            );

        $periodo = $query->first();
        abort_unless($periodo, 422, 'El periodo seleccionado no pertenece al ciclo o a la carga académica.');

        return $periodo;
    }

    private function horarioBaseDeAsignacion(AsignacionMateria $asignacion): Horario
    {
        $base = $asignacion->horarios->first() ?? new Horario([
            'nivel_id' => $asignacion->nivel_id,
            'grado_id' => $asignacion->grado_id,
            'generacion_id' => $asignacion->generacion_id,
            'semestre_id' => $asignacion->semestre_id,
            'grupo_id' => $asignacion->grupo_id,
            'asignacion_materia_id' => $asignacion->id,
            'ciclo_escolar_id' => $asignacion->ciclo_escolar_id,
        ]);

        $base->setRelation('asignacionMateria', $asignacion);
        $base->setRelation('nivel', $asignacion->nivel ?? $asignacion->grupo?->nivel);
        $base->setRelation('grado', $asignacion->grado ?? $asignacion->grupo?->grado);
        $base->setRelation('generacion', $asignacion->generacion ?? $asignacion->grupo?->generacion);
        $base->setRelation('semestre', $asignacion->semestre ?? $asignacion->grupo?->semestre);
        $base->setRelation('grupo', $asignacion->grupo);

        return $base;
    }

    private function horarioBaseDeTaller(TallerSesion $sesion, Grupo $grupo): Horario
    {
        $materia = new Materia([
            'materia' => $sesion->taller?->nombre ?? 'Talleres',
            'clave' => $sesion->taller?->clave,
            'calificable' => false,
            'extra' => true,
            'receso' => false,
        ]);

        $asignacion = new AsignacionMateria([
            'materia_id' => null,
            'grupo_id' => $grupo->id,
            'profesor_id' => $sesion->profesor_id,
            'ciclo_escolar_id' => $sesion->ciclo_escolar_id,
            'nivel_id' => $grupo->nivel_id,
            'grado_id' => $grupo->grado_id,
            'generacion_id' => $grupo->generacion_id,
            'semestre_id' => $grupo->semestre_id,
            'estado' => $sesion->estado,
        ]);
        $asignacion->setRelation('materia', $materia);
        $asignacion->setRelation('profesor', $sesion->profesor);
        $asignacion->setRelation('grupo', $grupo);

        $horario = new Horario([
            'nivel_id' => $grupo->nivel_id,
            'grado_id' => $grupo->grado_id,
            'generacion_id' => $grupo->generacion_id,
            'semestre_id' => $grupo->semestre_id,
            'grupo_id' => $grupo->id,
            'taller_sesion_id' => $sesion->id,
            'ciclo_escolar_id' => $sesion->ciclo_escolar_id,
        ]);
        $horario->setRelation('asignacionMateria', $asignacion);
        $horario->setRelation('tallerSesion', $sesion);
        $horario->setRelation('nivel', $grupo->nivel ?? $sesion->taller?->nivel);
        $horario->setRelation('grado', $grupo->grado);
        $horario->setRelation('generacion', $grupo->generacion);
        $horario->setRelation('semestre', $grupo->semestre);
        $horario->setRelation('grupo', $grupo);
        $horario->setRelation('dia', $sesion->dia);
        $horario->setRelation('hora', $sesion->hora);

        return $horario;
    }

    private function esBachillerato(?int $nivelId, ?string $slug): bool
    {
        return (int) $nivelId === 4 || $slug === 'bachillerato';
    }
}
