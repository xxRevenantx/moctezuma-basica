<?php

namespace App\Services;

use App\Models\Calificacion;
use App\Models\CambioAcademico;
use App\Models\Constancia;
use App\Models\DocumentoAlumno;
use App\Models\Inscripcion;
use App\Models\InscripcionTutor;
use App\Models\MatriculaAlumno;
use App\Models\MovimientoAlumno;
use App\Models\Oficio;
use App\Models\RiesgoAcademicoEvaluacion;
use App\Models\SeguimientoAcademicoCaso;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Expediente360AlumnoService
{
    /** @return array<string, mixed> */
    public function construir(int $inscripcionId): array
    {
        $alumno = Inscripcion::withTrashed()
            ->with([
                'nivel:id,nombre,slug,color',
                'grado:id,nombre,slug,orden',
                'generacion:id,nivel_id,nombre,anio_ingreso,anio_egreso,status,estado_cierre',
                'semestre:id,grado_id,numero,orden_global',
                'grupo:id,clave,nivel_id,grado_id,generacion_id,semestre_id,ciclo_escolar_id,asignacion_grupo_id',
                'grupo.asignacionGrupo:id,nombre',
                'ciclo:id,ciclo',
                'cicloEscolar:id,inicio_anio,fin_anio,es_actual,cerrado_at',
            ])
            ->findOrFail($inscripcionId);

        $trayectoria = app(LineaTiempoAcademicaAlumnoService::class)->construir($alumno->id);

        $responsables = InscripcionTutor::query()
            ->where('inscripcion_id', $alumno->id)
            ->with('tutor:id,curp,identificador_alternativo,parentesco,nombre,apellido_paterno,apellido_materno,telefono_casa,telefono_celular,correo_electronico,activo')
            ->orderByDesc('activo')
            ->orderByDesc('es_principal')
            ->orderBy('orden_contacto')
            ->get()
            ->map(fn (InscripcionTutor $relacion): array => [
                'id' => $relacion->id,
                'tutor_id' => $relacion->tutor_id,
                'nombre' => $relacion->tutor?->nombre_completo ?: 'Responsable sin nombre',
                'parentesco' => $relacion->parentesco ?: $relacion->tutor?->parentesco ?: 'No especificado',
                'telefono' => $relacion->tutor?->telefono_celular ?: $relacion->tutor?->telefono_casa ?: '—',
                'correo' => $relacion->tutor?->correo_electronico ?: '—',
                'identidad' => $relacion->tutor?->identidad_protegida ?: 'Sin identificador',
                'activo' => (bool) $relacion->activo,
                'principal' => (bool) $relacion->es_principal,
                'tutor_legal' => (bool) $relacion->es_tutor_legal,
                'vive_con_alumno' => (bool) $relacion->vive_con_alumno,
                'recibe_avisos' => (bool) $relacion->recibe_avisos,
                'recibe_calificaciones' => (bool) $relacion->recibe_calificaciones,
                'contacto_emergencia' => (bool) $relacion->contacto_emergencia,
                'autorizado_recoger' => (bool) $relacion->autorizado_recoger,
                'responsable_economico' => (bool) $relacion->responsable_economico,
                'observaciones' => $relacion->observaciones,
            ])
            ->values();

        if ($responsables->isEmpty() && $alumno->tutor_id) {
            $tutor = $alumno->tutor()->first();
            if ($tutor) {
                $responsables->push([
                    'id' => null,
                    'tutor_id' => $tutor->id,
                    'nombre' => $tutor->nombre_completo,
                    'parentesco' => $tutor->parentesco ?: 'No especificado',
                    'telefono' => $tutor->telefono_celular ?: $tutor->telefono_casa ?: '—',
                    'correo' => $tutor->correo_electronico ?: '—',
                    'identidad' => $tutor->identidad_protegida,
                    'activo' => (bool) $tutor->activo,
                    'principal' => true,
                    'tutor_legal' => false,
                    'vive_con_alumno' => false,
                    'recibe_avisos' => true,
                    'recibe_calificaciones' => true,
                    'contacto_emergencia' => true,
                    'autorizado_recoger' => false,
                    'responsable_economico' => false,
                    'observaciones' => 'Relación heredada del registro principal.',
                ]);
            }
        }

        $documentos = DocumentoAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->with([
                'tipoDocumento:id,nombre,slug,orden',
                'cicloEscolar:id,inicio_anio,fin_anio,es_actual',
                'usuarioQueSubio:id,name',
                'usuarioQueValido:id,name',
            ])
            ->latest('created_at')
            ->get();

        $calificaciones = Calificacion::query()
            ->where('inscripcion_id', $alumno->id)
            ->with([
                'cicloEscolar:id,inicio_anio,fin_anio,es_actual',
                'nivel:id,nombre',
                'grado:id,nombre',
                'semestre:id,numero',
                'periodo.periodoBasica:id,periodo,descripcion',
                'periodo.parcialBachillerato:id,parcial,descripcion',
                'periodo.mesesBasica:id,meses,meses_corto',
                'periodo.mesesBachillerato:id,meses,meses_corto',
                'asignacionMateria.materia:id,materia',
                'capturador:id,name',
            ])
            ->latest('fecha_captura')
            ->latest('id')
            ->get();

        $calificacionesPorCiclo = $calificaciones
            ->groupBy(fn (Calificacion $item): string => $item->cicloEscolar?->nombre ?? 'Sin ciclo')
            ->map(function (Collection $items, string $ciclo): array {
                $numericas = $items->filter(fn (Calificacion $item) => (bool) $item->es_numerica && $item->valor_numerico !== null);

                return [
                    'ciclo' => $ciclo,
                    'total' => $items->count(),
                    'promedio' => $numericas->isNotEmpty() ? number_format((float) $numericas->avg('valor_numerico'), 1) : '—',
                    'nivel' => $items->first()?->nivel?->nombre,
                    'grado' => $items->first()?->semestre?->numero
                        ? $items->first()->semestre->numero.'° semestre'
                        : $items->first()?->grado?->nombre,
                    'registros' => $items->take(30)->map(fn (Calificacion $item): array => [
                        'id' => $item->id,
                        'materia' => $item->asignacionMateria?->materia?->materia ?: 'Registro académico',
                        'periodo' => $this->etiquetaPeriodo($item),
                        'calificacion' => $item->calificacion ?: ($item->valor_numerico !== null ? (string) $item->valor_numerico : '—'),
                        'numerica' => (bool) $item->es_numerica,
                        'observacion' => $item->observacion,
                        'capturado_por' => $item->capturador?->name,
                        'capturado_at' => $item->fecha_captura?->format('d/m/Y H:i'),
                    ])->values()->all(),
                ];
            })
            ->values();

        $matriculas = MatriculaAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->with(['nivel:id,nombre', 'usuario:id,name'])
            ->orderByDesc('vigente')
            ->orderByDesc('fecha_asignacion')
            ->get()
            ->map(fn (MatriculaAlumno $matricula): array => [
                'id' => $matricula->id,
                'matricula' => $matricula->matricula,
                'nivel' => $matricula->nivel?->nombre,
                'fecha_asignacion' => $matricula->fecha_asignacion?->format('d/m/Y'),
                'fecha_fin' => $matricula->fecha_fin?->format('d/m/Y'),
                'vigente' => (bool) $matricula->vigente,
                'origen' => Str::of((string) $matricula->origen)->replace('_', ' ')->title()->toString(),
                'registrado_por' => $matricula->usuario?->name,
            ]);

        $movimientos = MovimientoAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->with(['usuario:id,name', 'nivelAnterior:id,nombre', 'nivelNuevo:id,nombre', 'cicloEscolar:id,inicio_anio,fin_anio'])
            ->latest('fecha')
            ->latest('id')
            ->get()
            ->map(fn (MovimientoAlumno $movimiento): array => [
                'key' => 'movimiento-'.$movimiento->id,
                'tipo' => Str::of((string) $movimiento->tipo)->replace('_', ' ')->title()->toString(),
                'fecha' => $movimiento->fecha?->format('d/m/Y') ?? $movimiento->created_at?->format('d/m/Y H:i'),
                'motivo' => $movimiento->motivo ?: 'Sin motivo capturado',
                'observaciones' => $movimiento->observaciones,
                'usuario' => $movimiento->usuario?->name,
                'ciclo' => $movimiento->cicloEscolar?->nombre,
                'cambio_nivel' => collect([$movimiento->nivelAnterior?->nombre, $movimiento->nivelNuevo?->nombre])->filter()->implode(' → '),
            ]);

        $cambios = CambioAcademico::query()
            ->where('inscripcion_id', $alumno->id)
            ->with('usuario:id,name')
            ->latest('realizado_at')
            ->latest('id')
            ->get()
            ->map(fn (CambioAcademico $cambio): array => [
                'key' => 'cambio-'.$cambio->id,
                'tipo' => Str::of((string) $cambio->tipo)->replace('_', ' ')->title()->toString(),
                'fecha' => $cambio->realizado_at?->format('d/m/Y H:i') ?? $cambio->created_at?->format('d/m/Y H:i'),
                'motivo' => $cambio->motivo ?: 'Cambio académico registrado',
                'observaciones' => null,
                'usuario' => $cambio->usuario?->name,
                'ciclo' => null,
                'cambio_nivel' => null,
            ]);

        $documentosEmitidos = collect();
        if (Schema::hasTable('constancias')) {
            $documentosEmitidos = $documentosEmitidos->concat(
                Constancia::withTrashed()
                    ->where('inscripcion_id', $alumno->id)
                    ->with('plantilla:id,titulo')
                    ->latest('fecha_expedicion')
                    ->latest('id')
                    ->get()
                    ->map(fn (Constancia $constancia): array => [
                        'tipo' => 'Constancia',
                        'nombre' => $constancia->plantilla?->titulo ?: 'Constancia institucional',
                        'folio' => $constancia->folio ?: '—',
                        'fecha' => $constancia->fecha_expedicion?->format('d/m/Y') ?: $constancia->created_at?->format('d/m/Y'),
                        'estado' => $constancia->estado_documento ?: ($constancia->deleted_at ? 'cancelada' : 'emitida'),
                    ])
            );
        }

        if (Schema::hasTable('oficios')) {
            $documentosEmitidos = $documentosEmitidos->concat(
                Oficio::withTrashed()
                    ->where('inscripcion_id', $alumno->id)
                    ->latest('id')
                    ->get()
                    ->map(fn (Oficio $oficio): array => [
                        'tipo' => 'Oficio',
                        'nombre' => $oficio->asunto ?: Str::of((string) $oficio->tipo_oficio)->replace('_', ' ')->title()->toString(),
                        'folio' => $oficio->folio ?: '—',
                        'fecha' => $oficio->created_at?->format('d/m/Y'),
                        'estado' => $oficio->deleted_at ? 'cancelado' : 'emitido',
                    ])
            );
        }

        $observaciones = $alumno->observacionesInscripcion()
            ->with(['cicloEscolar:id,inicio_anio,fin_anio,es_actual', 'actualizador:id,name'])
            ->latest('updated_at')
            ->get()
            ->map(fn ($observacion): array => [
                'id' => $observacion->id,
                'ciclo' => $observacion->cicloEscolar?->nombre ?: 'Sin ciclo',
                'es_actual' => (bool) ($observacion->cicloEscolar?->es_actual ?? false),
                'contenido' => app(ObservacionInscripcionService::class)->sanitizar($observacion->contenido),
                'usuario' => $observacion->actualizador?->name ?: 'Sistema',
                'fecha' => $observacion->updated_at?->format('d/m/Y H:i'),
            ]);

        $riesgo = null;
        $seguimientos = collect();
        if (Schema::hasTable('riesgo_academico_evaluaciones')) {
            $evaluacion = RiesgoAcademicoEvaluacion::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('es_actual', true)
                ->latest('evaluado_at')
                ->first();

            if ($evaluacion) {
                $riesgo = [
                    'nivel' => $evaluacion->nivel_riesgo,
                    'etiqueta' => $evaluacion->etiqueta_riesgo,
                    'puntaje' => $evaluacion->puntaje,
                    'evaluado_at' => $evaluacion->evaluado_at?->format('d/m/Y H:i'),
                    'factores' => collect($evaluacion->factores ?? [])->take(6)->values()->all(),
                ];
            }
        }

        if (Schema::hasTable('seguimiento_academico_casos')) {
            $seguimientos = SeguimientoAcademicoCaso::query()
                ->where('inscripcion_id', $alumno->id)
                ->with(['responsable:id,name'])
                ->latest('id')
                ->get()
                ->map(fn (SeguimientoAcademicoCaso $caso): array => [
                    'id' => $caso->id,
                    'folio' => $caso->folio,
                    'estado' => Str::of((string) $caso->estado)->replace('_', ' ')->title()->toString(),
                    'prioridad' => Str::of((string) $caso->prioridad)->replace('_', ' ')->title()->toString(),
                    'resumen' => $caso->resumen,
                    'responsable' => $caso->responsable?->name,
                    'proxima_revision' => $caso->proxima_revision_at?->format('d/m/Y'),
                    'activo' => in_array($caso->estado, ['abierto', 'en_seguimiento', 'pausado'], true),
                ]);
        }

        $documentosActuales = $documentos->where('es_actual', true)->where('es_fuente', false);
        $calificacionesNumericas = $calificaciones->filter(fn (Calificacion $item) => (bool) $item->es_numerica && $item->valor_numerico !== null);

        return [
            'alumno' => [
                'id' => $alumno->id,
                'nombre' => $this->nombreCompleto($alumno),
                'iniciales' => $alumno->iniciales,
                'foto_url' => $alumno->foto_existe ? $alumno->foto_url : null,
                'matricula' => $alumno->matricula ?: 'Sin matrícula',
                'folio' => $alumno->folio ?: '—',
                'curp' => $alumno->curp ?: '—',
                'genero' => $this->genero($alumno->genero),
                'fecha_nacimiento' => $alumno->fecha_nacimiento?->format('d/m/Y') ?: '—',
                'edad' => $alumno->fecha_nacimiento?->age,
                'estatus' => $alumno->trashed() ? 'archivado' : $alumno->estatusNormalizado(),
                'estatus_etiqueta' => $alumno->trashed() ? 'Archivado' : $alumno->etiqueta_estatus,
                'archivado' => $alumno->trashed(),
                'nivel' => $alumno->nivel?->nombre,
                'nivel_slug' => $alumno->nivel?->slug,
                'grado' => $alumno->semestre?->numero ? $alumno->semestre->numero.'° semestre' : $alumno->grado?->nombre,
                'grupo' => $alumno->grupo?->asignacionGrupo?->nombre ?: $alumno->grupo?->clave,
                'generacion' => $alumno->generacion?->etiqueta,
                'ciclo' => $alumno->cicloEscolar?->nombre ?: $alumno->ciclo?->ciclo,
                'fecha_inscripcion' => $alumno->fecha_inscripcion?->format('d/m/Y') ?: '—',
                'fecha_estatus' => $alumno->fecha_estatus?->format('d/m/Y H:i'),
                'motivo_estatus' => $alumno->motivo_estatus,
                'direccion' => collect([
                    trim(collect([$alumno->calle, $alumno->numero_exterior])->filter()->implode(' ')),
                    $alumno->colonia,
                    $alumno->municipio,
                    $alumno->estado_residencia,
                    $alumno->codigo_postal ? 'C.P. '.$alumno->codigo_postal : null,
                ])->filter()->implode(', ') ?: 'Sin domicilio capturado',
                'nacimiento' => collect([$alumno->lugar_nacimiento, $alumno->municipio_nacimiento ?? null, $alumno->estado_nacimiento, $alumno->pais_nacimiento])->filter()->implode(', ') ?: 'Sin lugar de nacimiento capturado',
            ],
            'resumen' => [
                'ciclos' => $trayectoria['resumen']['ciclos'] ?? 0,
                'promedio_global' => $calificacionesNumericas->isNotEmpty() ? number_format((float) $calificacionesNumericas->avg('valor_numerico'), 1) : '—',
                'documentos' => $documentosActuales->count(),
                'documentos_validados' => $documentosActuales->where('estado', 'validado')->count(),
                'responsables' => $responsables->where('activo', true)->count(),
                'movimientos' => $movimientos->count() + $cambios->count(),
                'documentos_emitidos' => $documentosEmitidos->count(),
                'seguimientos_activos' => $seguimientos->where('activo', true)->count(),
            ],
            'integridad' => $trayectoria['integridad'],
            'trayectoria' => $trayectoria,
            'responsables' => $responsables->all(),
            'matriculas' => $matriculas->all(),
            'calificaciones' => $calificacionesPorCiclo->all(),
            'documentos' => $documentos->take(40)->map(fn (DocumentoAlumno $documento): array => [
                'id' => $documento->id,
                'nombre' => $documento->tipoDocumento?->nombre ?: $documento->nombre_original ?: 'Documento',
                'archivo' => $documento->nombre_original,
                'estado' => $documento->estado,
                'actual' => (bool) $documento->es_actual,
                'version' => $documento->version,
                'tamano' => $documento->tamano_legible,
                'paginas' => $documento->paginas_total,
                'ciclo' => $documento->cicloEscolar?->nombre,
                'fecha' => $documento->fecha_documento?->format('d/m/Y') ?: $documento->created_at?->format('d/m/Y H:i'),
                'subido_por' => $documento->usuarioQueSubio?->name,
                'validado_por' => $documento->usuarioQueValido?->name,
                'validado_at' => $documento->validado_at?->format('d/m/Y H:i'),
            ])->values()->all(),
            'documentos_resumen' => [
                'total' => $documentos->count(),
                'actuales' => $documentosActuales->count(),
                'validados' => $documentosActuales->where('estado', 'validado')->count(),
                'pendientes' => $documentosActuales->whereIn('estado', ['pendiente', 'recibido'])->count(),
                'rechazados' => $documentosActuales->where('estado', 'rechazado')->count(),
            ],
            'documentos_emitidos' => $documentosEmitidos->sortByDesc('fecha')->values()->all(),
            'movimientos' => $movimientos->concat($cambios)->sortByDesc('fecha')->values()->all(),
            'observaciones' => $observaciones->all(),
            'riesgo' => $riesgo,
            'seguimientos' => $seguimientos->all(),
            'generado_at' => now()->format('d/m/Y H:i'),
        ];
    }

    private function etiquetaPeriodo(Calificacion $calificacion): string
    {
        $periodo = $calificacion->periodo;
        if (! $periodo) {
            return 'Sin periodo';
        }

        return (string) (
            $periodo->periodoBasica?->descripcion
            ?: $periodo->periodoBasica?->periodo
            ?: $periodo->parcialBachillerato?->descripcion
            ?: $periodo->parcialBachillerato?->parcial
            ?: $periodo->mesesBasica?->meses
            ?: $periodo->mesesBachillerato?->meses
            ?: 'Periodo académico'
        );
    }

    private function nombreCompleto(Inscripcion $alumno): string
    {
        return trim(collect([$alumno->nombre, $alumno->apellido_paterno, $alumno->apellido_materno])->filter()->implode(' ')) ?: 'Alumno sin nombre';
    }

    private function genero(?string $genero): string
    {
        return match (mb_strtoupper(trim((string) $genero))) {
            'H', 'MASCULINO', 'HOMBRE' => 'Hombre',
            'M', 'F', 'FEMENINO', 'MUJER' => 'Mujer',
            default => 'No especificado',
        };
    }
}
