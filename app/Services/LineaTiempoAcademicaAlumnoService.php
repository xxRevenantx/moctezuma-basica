<?php

namespace App\Services;

use App\Models\BitacoraCalificacion;
use App\Models\Calificacion;
use App\Models\CalificacionCorreccion;
use App\Models\CambioAcademico;
use App\Models\DocumentoAlumno;
use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use App\Models\MatriculaAlumno;
use App\Models\MovimientoAlumno;
use App\Models\PreinscripcionCiclo;
use App\Models\ProcesoCierreCicloDetalle;
use App\Models\ProyeccionContinuidad;
use App\Models\RiesgoAcademicoEvaluacion;
use App\Models\SeguimientoAcademicoEvento;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LineaTiempoAcademicaAlumnoService
{
    /**
     * Construye una representación serializable de la trayectoria de un alumno.
     * La fuente principal es inscripcion_ciclos; inscripciones solo representa
     * la ubicación institucional actual.
     *
     * @return array<string, mixed>
     */
    public function construir(int $inscripcionId): array
    {
        $alumno = Inscripcion::withTrashed()
            ->with([
                'nivel:id,nombre,slug,color',
                'grado:id,nombre,slug,orden',
                'generacion:id,nivel_id,anio_ingreso,anio_egreso,status',
                'semestre:id,grado_id,numero,orden_global',
                'ciclo:id,ciclo',
                'cicloEscolar:id,inicio_anio,fin_anio,es_actual,cerrado_at',
                'grupo' => fn ($query) => $query
                    ->select(['id', 'asignacion_grupo_id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id'])
                    ->with('asignacionGrupo:id,nombre'),
            ])
            ->findOrFail($inscripcionId);

        $historiales = InscripcionCiclo::query()
            ->where('inscripcion_id', $alumno->id)
            ->with([
                'cicloEscolar:id,inicio_anio,fin_anio,es_actual,cerrado_at',
                'nivel:id,nombre,slug,color',
                'grado:id,nombre,slug,orden',
                'generacion:id,nivel_id,anio_ingreso,anio_egreso,status',
                'semestre:id,grado_id,numero,orden_global',
                'usuarioCerro:id,name',
                'grupo' => fn ($query) => $query
                    ->select(['id', 'asignacion_grupo_id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id'])
                    ->with('asignacionGrupo:id,nombre'),
                'asignaciones' => fn ($query) => $query
                    ->with([
                        'nivel:id,nombre,slug,color',
                        'grado:id,nombre,slug,orden',
                        'generacion:id,nivel_id,anio_ingreso,anio_egreso,status',
                        'semestre:id,grado_id,numero,orden_global',
                        'usuarioRegistro:id,name',
                        'grupo' => fn ($grupo) => $grupo
                            ->select(['id', 'asignacion_grupo_id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id'])
                            ->with('asignacionGrupo:id,nombre'),
                    ])
                    ->orderBy('fecha_inicio')
                    ->orderBy('id'),
            ])
            ->get()
            ->sortBy(fn (InscripcionCiclo $historial) => sprintf(
                '%04d-%010d',
                (int) ($historial->cicloEscolar?->inicio_anio ?? 0),
                (int) $historial->id
            ))
            ->values();

        $calificaciones = Calificacion::query()
            ->where('inscripcion_id', $alumno->id)
            ->with([
                'periodo.periodoBasica:id,periodo,descripcion',
                'periodo.mesesBasica:id,meses,meses_corto',
                'periodo.mesesBachillerato:id,meses,meses_corto',
                'periodo.parcialBachillerato:id,parcial,descripcion',
                'capturador:id,name',
            ])
            ->get();

        $bitacora = BitacoraCalificacion::query()
            ->where('inscripcion_id', $alumno->id)
            ->with([
                'periodo.periodoBasica:id,periodo,descripcion',
                'periodo.mesesBasica:id,meses,meses_corto',
                'periodo.mesesBachillerato:id,meses,meses_corto',
                'periodo.parcialBachillerato:id,parcial,descripcion',
                'asignacionMateria.materia:id,materia',
                'usuario:id,name',
            ])
            ->latest('id')
            ->get();

        $correcciones = CalificacionCorreccion::query()
            ->where('inscripcion_id', $alumno->id)
            ->with([
                'periodo.periodoBasica:id,periodo,descripcion',
                'periodo.mesesBasica:id,meses,meses_corto',
                'periodo.mesesBachillerato:id,meses,meses_corto',
                'periodo.parcialBachillerato:id,parcial,descripcion',
                'calificacion.asignacionMateria.materia:id,materia',
                'solicitante:id,name',
                'autorizador:id,name',
            ])
            ->latest('id')
            ->get();

        $documentos = DocumentoAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->with([
                'tipoDocumento:id,nombre,slug,es_general,requiere_nivel,orden',
                'usuarioQueSubio:id,name',
                'usuarioQueValido:id,name',
            ])
            ->orderBy('fecha_documento')
            ->orderBy('created_at')
            ->get();

        $movimientos = MovimientoAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->with(['usuario:id,name', 'nivelAnterior:id,nombre', 'nivelNuevo:id,nombre'])
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        $cambios = CambioAcademico::query()
            ->where('inscripcion_id', $alumno->id)
            ->with('usuario:id,name')
            ->orderBy('realizado_at')
            ->orderBy('id')
            ->get();

        $preinscripciones = PreinscripcionCiclo::query()
            ->where('inscripcion_id', $alumno->id)
            ->with([
                'cicloEscolar:id,inicio_anio,fin_anio,es_actual,cerrado_at',
                'nivel:id,nombre',
                'grado:id,nombre',
                'semestre:id,numero',
                'usuarioFormalizo:id,name',
                'usuarioCancelo:id,name',
                'grupo' => fn ($query) => $query
                    ->select(['id', 'asignacion_grupo_id'])
                    ->with('asignacionGrupo:id,nombre'),
            ])
            ->orderBy('fecha_preinscripcion')
            ->get();

        $proyecciones = ProyeccionContinuidad::query()
            ->where('inscripcion_id', $alumno->id)
            ->with([
                'cicloDestino:id,inicio_anio,fin_anio,es_actual,cerrado_at',
                'nivelDestino:id,nombre',
                'gradoDestino:id,nombre',
                'semestreDestino:id,numero',
                'usuarioProyecto:id,name',
                'usuarioConfirmo:id,name',
                'usuarioCancelo:id,name',
                'usuarioRevirtio:id,name',
                'grupoDestino' => fn ($query) => $query
                    ->select(['id', 'asignacion_grupo_id'])
                    ->with('asignacionGrupo:id,nombre'),
            ])
            ->orderBy('fecha_proyeccion')
            ->orderBy('id')
            ->get();

        $cierres = ProcesoCierreCicloDetalle::query()
            ->where('inscripcion_id', $alumno->id)
            ->with([
                'proceso.usuarioRealizo:id,name',
                'proceso.usuarioRevirtio:id,name',
                'proceso.cicloEscolar:id,inicio_anio,fin_anio,es_actual,cerrado_at',
            ])
            ->orderBy('id')
            ->get();

        $matriculas = MatriculaAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->with(['nivel:id,nombre', 'usuario:id,name'])
            ->orderBy('fecha_asignacion')
            ->orderBy('id')
            ->get();

        $evaluacionesRiesgo = RiesgoAcademicoEvaluacion::query()
            ->where('inscripcion_id', $alumno->id)
            ->with('evaluador:id,name')
            ->orderBy('evaluado_at')
            ->orderBy('id')
            ->get();

        $eventosSeguimiento = SeguimientoAcademicoEvento::query()
            ->whereHas('caso', fn ($query) => $query->where('inscripcion_id', $alumno->id))
            ->with([
                'caso:id,inscripcion_id,inscripcion_ciclo_id,folio,estado,riesgo_actual,puntaje_actual',
                'evaluacion:id,nivel_riesgo,puntaje',
                'usuario:id,name',
            ])
            ->orderBy('ocurrido_at')
            ->orderBy('id')
            ->get();

        $alertas = $this->detectarAlertas($alumno, $historiales, $calificaciones, $bitacora);
        $ciclos = $this->construirCiclos(
            $historiales,
            $calificaciones,
            $bitacora,
            $correcciones,
            $documentos,
            $movimientos,
            $cambios,
            $preinscripciones,
            $proyecciones,
            $cierres,
            $matriculas,
            $evaluacionesRiesgo,
            $eventosSeguimiento
        );

        if ($ciclos->isEmpty()) {
            $ciclos->push($this->cicloSinteticoActual($alumno));
            $alertas[] = [
                'nivel' => 'advertencia',
                'titulo' => 'Historial por ciclo no disponible',
                'detalle' => 'La vista usa temporalmente la ubicación actual de la matrícula. Ejecuta la auditoría académica para reconstruir el historial.',
            ];
        }

        $promedios = $calificaciones
            ->filter(fn (Calificacion $item) => (bool) $item->es_numerica && $item->valor_numerico !== null)
            ->map(fn (Calificacion $item) => (float) $item->valor_numerico);

        $primerAnio = $ciclos->pluck('anio_inicio')->filter()->min();
        $ultimoAnio = $ciclos->pluck('anio_fin')->filter()->max();
        $anosTrayectoria = ($primerAnio && $ultimoAnio)
            ? max(1, ((int) $ultimoAnio - (int) $primerAnio))
            : 1;

        return [
            'alumno' => $this->datosAlumno($alumno),
            'resumen' => [
                'ciclos' => $ciclos->count(),
                'anos_trayectoria' => $anosTrayectoria,
                'niveles' => $ciclos->pluck('nivel')->filter()->unique()->count(),
                'promociones' => $historiales->filter(fn (InscripcionCiclo $item) => in_array(
                    (string) $item->resultado_final,
                    ['promovido', 'promovido_grado', 'promovido_nivel', 'egresado'],
                    true
                ))->count(),
                'calificaciones' => $calificaciones->count(),
                'promedio_global' => $promedios->isNotEmpty() ? number_format($promedios->avg(), 1) : '—',
                'documentos' => $documentos->count(),
                'movimientos' => $movimientos->count() + $cambios->count(),
                'correcciones' => $bitacora->count() + $correcciones->count(),
                'evaluaciones_riesgo' => $evaluacionesRiesgo->count(),
                'eventos_seguimiento' => $eventosSeguimiento->count(),
                'riesgo_actual' => $evaluacionesRiesgo->where('es_actual', true)->sortByDesc('evaluado_at')->first()?->nivel_riesgo,
                'puntaje_riesgo_actual' => $evaluacionesRiesgo->where('es_actual', true)->sortByDesc('evaluado_at')->first()?->puntaje,
            ],
            'integridad' => [
                'estado' => $alertas === [] ? 'correcto' : 'revision',
                'etiqueta' => $alertas === [] ? 'Historial consistente' : 'Requiere revisión',
                'mensaje' => $alertas === []
                    ? 'Los registros consultados están vinculados con su ciclo académico.'
                    : 'Se encontraron datos reconstruidos o registros que conviene revisar.',
                'alertas' => $alertas,
            ],
            'ciclos' => $ciclos->values()->all(),
            'generado_at' => now()->format('d/m/Y H:i'),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function construirCiclos(
        Collection $historiales,
        Collection $calificaciones,
        Collection $bitacora,
        Collection $correcciones,
        Collection $documentos,
        Collection $movimientos,
        Collection $cambios,
        Collection $preinscripciones,
        Collection $proyecciones,
        Collection $cierres,
        Collection $matriculas,
        Collection $evaluacionesRiesgo,
        Collection $eventosSeguimiento
    ): Collection {
        return $historiales->map(function (InscripcionCiclo $historial) use (
            $calificaciones,
            $bitacora,
            $correcciones,
            $documentos,
            $movimientos,
            $cambios,
            $preinscripciones,
            $proyecciones,
            $cierres,
            $matriculas,
            $evaluacionesRiesgo,
            $eventosSeguimiento
        ): array {
            $calificacionesCiclo = $calificaciones->filter(fn (Calificacion $item) => $this->perteneceAlCiclo($item, $historial));
            $bitacoraCiclo = $bitacora->filter(fn (BitacoraCalificacion $item) => $this->perteneceAlCiclo($item, $historial));
            $correccionesCiclo = $correcciones->filter(fn (CalificacionCorreccion $item) => $this->perteneceAlCiclo($item, $historial));
            $documentosCiclo = $documentos->filter(fn (DocumentoAlumno $item) => (int) $item->ciclo_escolar_id === (int) $historial->ciclo_escolar_id);
            $movimientosCiclo = $movimientos->filter(fn (MovimientoAlumno $item) => $this->perteneceAlCiclo($item, $historial));
            $cambiosCiclo = $cambios->filter(fn (CambioAcademico $item) => $this->perteneceAlCiclo($item, $historial));
            $preinscripcionesCiclo = $preinscripciones->filter(fn (PreinscripcionCiclo $item) => (int) $item->ciclo_escolar_id === (int) $historial->ciclo_escolar_id);
            $proyeccionesCiclo = $proyecciones->filter(fn (ProyeccionContinuidad $item) => (int) $item->inscripcion_ciclo_origen_id === (int) $historial->id);
            $cierresCiclo = $cierres->filter(fn (ProcesoCierreCicloDetalle $item) => (int) $item->inscripcion_ciclo_origen_id === (int) $historial->id);
            $matriculasCiclo = $matriculas->filter(fn (MatriculaAlumno $item) => $this->fechaDentroDelCiclo($item->fecha_asignacion, $historial));
            $evaluacionesRiesgoCiclo = $evaluacionesRiesgo->filter(fn (RiesgoAcademicoEvaluacion $item) => $this->perteneceAlCiclo($item, $historial));
            $eventosSeguimientoCiclo = $eventosSeguimiento->filter(fn (SeguimientoAcademicoEvento $item) => (int) ($item->caso?->inscripcion_ciclo_id ?? 0) === (int) $historial->id);

            $eventos = collect();

            foreach ($preinscripcionesCiclo as $preinscripcion) {
                $eventos->push($this->evento(
                    'preinscripcion-'.$preinscripcion->id,
                    'preinscripcion',
                    $preinscripcion->fecha_preinscripcion ?? $preinscripcion->created_at,
                    'Preinscripción registrada',
                    $this->ubicacionDesdeModelo($preinscripcion),
                    $preinscripcion->estado ? 'Estado: '.$this->etiqueta($preinscripcion->estado) : null,
                    $preinscripcion->usuarioFormalizo?->name ?? $preinscripcion->usuarioCancelo?->name,
                    'clipboard-document-check',
                    'amber'
                ));
            }

            $eventos->push($this->evento(
                'ingreso-'.$historial->id,
                'ingreso',
                $historial->fecha_ingreso ?? $historial->created_at,
                $historial->estatus_ingreso === 'reingreso' ? 'Reingreso al ciclo escolar' : 'Ingreso al ciclo escolar',
                $this->ubicacionDesdeModelo($historial),
                $historial->matricula ? 'Matrícula: '.$historial->matricula : null,
                null,
                'academic-cap',
                'sky'
            ));

            foreach ($historial->asignaciones as $indice => $asignacion) {
                $eventos->push($this->evento(
                    'asignacion-'.$asignacion->id,
                    'ubicacion',
                    $asignacion->fecha_inicio ?? $asignacion->created_at,
                    $indice === 0 ? 'Asignación académica inicial' : $this->tituloAsignacion((string) $asignacion->tipo),
                    $this->ubicacionDesdeModelo($asignacion),
                    $asignacion->motivo ?: ($asignacion->es_actual ? 'Ubicación vigente en este ciclo' : null),
                    $asignacion->usuarioRegistro?->name,
                    $indice === 0 ? 'map-pin' : 'arrows-right-left',
                    $indice === 0 ? 'blue' : 'violet'
                ));
            }

            foreach ($matriculasCiclo as $matricula) {
                $eventos->push($this->evento(
                    'matricula-'.$matricula->id,
                    'matricula',
                    $matricula->fecha_asignacion ?? $matricula->created_at,
                    $matricula->vigente ? 'Matrícula asignada' : 'Matrícula histórica',
                    $matricula->matricula ?: 'Sin matrícula capturada',
                    $matricula->nivel?->nombre ? 'Nivel: '.$matricula->nivel->nombre : null,
                    $matricula->usuario?->name,
                    'identification',
                    'indigo'
                ));
            }

            foreach ($this->eventosCalificaciones($calificacionesCiclo) as $evento) {
                $eventos->push($evento);
            }

            foreach ($movimientosCiclo as $movimiento) {
                $eventos->push($this->evento(
                    'movimiento-'.$movimiento->id,
                    'movimiento',
                    $movimiento->fecha ?? $movimiento->created_at,
                    $this->tituloMovimiento((string) $movimiento->tipo),
                    $movimiento->motivo ?: $this->descripcionCambioEstado($movimiento->estado_anterior, $movimiento->estado_nuevo),
                    $movimiento->observaciones,
                    $movimiento->usuario?->name,
                    'arrows-right-left',
                    $this->tonoMovimiento((string) $movimiento->tipo)
                ));
            }

            foreach ($cambiosCiclo as $cambio) {
                $eventos->push($this->evento(
                    'cambio-'.$cambio->id,
                    'cambio',
                    $cambio->realizado_at ?? $cambio->created_at,
                    $this->tituloCambioAcademico((string) $cambio->tipo),
                    $cambio->motivo ?: $this->descripcionCambioEstado($cambio->datos_anteriores, $cambio->datos_nuevos),
                    null,
                    $cambio->usuario?->name,
                    'pencil-square',
                    'violet'
                ));
            }

            foreach ($documentosCiclo->take(18) as $documento) {
                $eventos->push($this->evento(
                    'documento-'.$documento->id,
                    'documento',
                    $documento->fecha_documento ?? $documento->created_at,
                    $documento->tipoDocumento?->nombre ?? 'Documento académico',
                    $this->descripcionDocumento($documento),
                    $documento->folio ? 'Folio: '.$documento->folio : null,
                    $documento->usuarioQueSubio?->name,
                    $documento->estado === 'validado' ? 'document-check' : 'document-text',
                    $documento->estado === 'rechazado' ? 'rose' : 'emerald'
                ));
            }

            foreach ($bitacoraCiclo->take(20) as $registro) {
                $materia = $registro->asignacionMateria?->materia?->materia ?? 'Materia';
                $cambio = trim(($registro->calificacion_anterior ?? '—').' → '.($registro->calificacion_nueva ?? '—'));

                $eventos->push($this->evento(
                    'bitacora-'.$registro->id,
                    'correccion',
                    $registro->created_at,
                    $this->tituloBitacora((string) $registro->accion),
                    $materia.': '.$cambio,
                    $registro->motivo ?: $registro->observacion,
                    $registro->usuario?->name,
                    'history',
                    'orange'
                ));
            }

            foreach ($correccionesCiclo->take(12) as $correccion) {
                $materia = $correccion->calificacion?->asignacionMateria?->materia?->materia ?? 'Calificación';

                $eventos->push($this->evento(
                    'correccion-'.$correccion->id,
                    'correccion',
                    $correccion->aplicada_at ?? $correccion->autorizada_at ?? $correccion->solicitada_at ?? $correccion->created_at,
                    'Corrección '.$this->etiqueta((string) $correccion->estado),
                    $materia.': '.$this->valorCorreccion($correccion->valor_anterior).' → '.$this->valorCorreccion($correccion->valor_propuesto),
                    $correccion->motivo,
                    $correccion->autorizador?->name ?? $correccion->solicitante?->name,
                    'shield-check',
                    $correccion->estado === 'rechazada' ? 'rose' : 'amber'
                ));
            }

            foreach ($evaluacionesRiesgoCiclo as $evaluacion) {
                $factores = collect($evaluacion->factores ?? [])
                    ->pluck('detalle')
                    ->filter()
                    ->take(3)
                    ->implode(' · ');

                $eventos->push($this->evento(
                    'riesgo-'.$evaluacion->id,
                    'riesgo',
                    $evaluacion->evaluado_at ?? $evaluacion->created_at,
                    'Semáforo de riesgo: '.$evaluacion->etiqueta_riesgo,
                    'Puntaje '.$evaluacion->puntaje.'/100'.($factores ? ' · '.$factores : ''),
                    $evaluacion->es_actual ? 'Evaluación vigente' : 'Evaluación histórica',
                    $evaluacion->evaluador?->name,
                    'chart-bar-square',
                    $this->tonoRiesgo((string) $evaluacion->nivel_riesgo)
                ));
            }

            foreach ($eventosSeguimientoCiclo as $seguimiento) {
                $eventos->push($this->evento(
                    'seguimiento-'.$seguimiento->id,
                    'seguimiento',
                    $seguimiento->ocurrido_at ?? $seguimiento->created_at,
                    $seguimiento->titulo ?: 'Seguimiento académico',
                    $seguimiento->descripcion ?: 'Evento registrado en el expediente de seguimiento.',
                    $seguimiento->caso?->folio ? 'Caso '.$seguimiento->caso->folio : null,
                    $seguimiento->usuario?->name,
                    $this->iconoSeguimiento((string) $seguimiento->tipo),
                    $this->tonoSeguimiento((string) $seguimiento->tipo)
                ));
            }

            foreach ($cierresCiclo as $detalle) {
                $proceso = $detalle->proceso;
                $eventos->push($this->evento(
                    'cierre-proceso-'.$detalle->id,
                    'cierre',
                    $proceso?->fecha_efectiva ?? $proceso?->realizado_at ?? $detalle->created_at,
                    $proceso?->revertido_at ? 'Cierre académico revertido' : 'Cierre académico procesado',
                    $detalle->observacion ?: $this->etiqueta((string) $detalle->resultado),
                    $proceso?->motivo,
                    $proceso?->revertido_at ? $proceso->usuarioRevirtio?->name : $proceso?->usuarioRealizo?->name,
                    $proceso?->revertido_at ? 'arrow-uturn-left' : 'shield-check',
                    $proceso?->revertido_at ? 'rose' : 'emerald'
                ));
            }

            foreach ($proyeccionesCiclo as $proyeccion) {
                $eventos->push($this->evento(
                    'proyeccion-'.$proyeccion->id,
                    'proyeccion',
                    $proyeccion->revertida_at ?? $proyeccion->confirmada_at ?? $proyeccion->cancelada_at ?? $proyeccion->fecha_proyeccion ?? $proyeccion->created_at,
                    $proyeccion->etiqueta_estado,
                    $this->descripcionProyeccion($proyeccion),
                    $proyeccion->estado === 'revertida'
                        ? ($proyeccion->motivo_reversion ?: $proyeccion->etiqueta_tipo)
                        : $proyeccion->etiqueta_tipo,
                    $proyeccion->usuarioRevirtio?->name ?? $proyeccion->usuarioConfirmo?->name ?? $proyeccion->usuarioCancelo?->name ?? $proyeccion->usuarioProyecto?->name,
                    match ($proyeccion->estado) {
                        'confirmada' => 'check-circle',
                        'cancelada' => 'x-circle',
                        'revertida' => 'arrow-uturn-left',
                        default => 'arrow-right',
                    },
                    match ($proyeccion->estado) {
                        'confirmada' => 'emerald',
                        'cancelada' => 'rose',
                        'revertida' => 'violet',
                        default => 'indigo',
                    }
                ));
            }

            if ($historial->estaCerrado()) {
                $eventos->push($this->evento(
                    'cierre-'.$historial->id,
                    'cierre',
                    $historial->fecha_salida ?? $historial->cerrado_at ?? $historial->updated_at,
                    'Ciclo académico concluido',
                    $historial->resultado_final
                        ? 'Resultado: '.$this->etiquetaResultado((string) $historial->resultado_final)
                        : 'El historial quedó cerrado.',
                    $historial->motivo_cierre,
                    $historial->usuarioCerro?->name,
                    'check-circle',
                    $this->tonoResultado((string) $historial->resultado_final)
                ));
            }

            $eventos = $eventos
                ->filter()
                ->sortBy(fn (array $evento) => sprintf('%012d-%s', (int) $evento['timestamp'], (string) $evento['id']))
                ->values();

            $numericas = $calificacionesCiclo
                ->filter(fn (Calificacion $item) => (bool) $item->es_numerica && $item->valor_numerico !== null)
                ->map(fn (Calificacion $item) => (float) $item->valor_numerico);

            return [
                'id' => $historial->id,
                'ciclo_escolar_id' => $historial->ciclo_escolar_id,
                'ciclo' => $historial->cicloEscolar?->nombre ?? 'Ciclo sin identificar',
                'anio_inicio' => (int) ($historial->cicloEscolar?->inicio_anio ?? 0),
                'anio_fin' => (int) ($historial->cicloEscolar?->fin_anio ?? 0),
                'es_actual' => (bool) ($historial->cicloEscolar?->es_actual ?? false),
                'estado' => $historial->estado,
                'estado_etiqueta' => $historial->etiqueta_estado,
                'estatus' => $historial->estatus_actual_ciclo,
                'estatus_etiqueta' => $historial->etiqueta_estatus,
                'resultado' => $historial->resultado_final,
                'resultado_etiqueta' => $historial->resultado_final
                    ? $this->etiquetaResultado((string) $historial->resultado_final)
                    : ($historial->estaEnCurso() ? 'En curso' : 'Sin resultado'),
                'tono' => $historial->estaEnCurso() ? 'sky' : $this->tonoResultado((string) $historial->resultado_final),
                'nivel' => $historial->nivel?->nombre,
                'grado' => $historial->grado?->nombre,
                'semestre' => $historial->semestre?->numero ? $historial->semestre->numero.'° semestre' : null,
                'grupo' => $historial->grupo?->asignacionGrupo?->nombre,
                'generacion' => $historial->generacion
                    ? $historial->generacion->anio_ingreso.'-'.$historial->generacion->anio_egreso
                    : null,
                'ubicacion' => $this->ubicacionDesdeModelo($historial),
                'fecha_ingreso' => $this->fechaLegible($historial->fecha_ingreso),
                'fecha_salida' => $this->fechaLegible($historial->fecha_salida),
                'matricula' => $historial->matricula,
                'reconstruido' => (bool) $historial->reconstruido,
                'nivel_confianza' => $historial->nivel_confianza ?: 'exacto',
                'asignaciones' => $historial->asignaciones->map(fn ($asignacion): array => [
                    'id' => $asignacion->id,
                    'ubicacion' => $this->ubicacionDesdeModelo($asignacion),
                    'tipo' => $this->etiqueta((string) $asignacion->tipo),
                    'motivo' => $asignacion->motivo,
                    'inicio' => $this->fechaLegible($asignacion->fecha_inicio),
                    'fin' => $this->fechaLegible($asignacion->fecha_fin),
                    'es_actual' => (bool) $asignacion->es_actual,
                ])->values()->all(),
                'estadisticas' => [
                    'calificaciones' => $calificacionesCiclo->count(),
                    'periodos' => $calificacionesCiclo->pluck('periodo_id')->filter()->unique()->count(),
                    'promedio' => $numericas->isNotEmpty() ? number_format($numericas->avg(), 1) : '—',
                    'documentos' => $documentosCiclo->count(),
                    'movimientos' => $movimientosCiclo->count() + $cambiosCiclo->count(),
                    'correcciones' => $bitacoraCiclo->count() + $correccionesCiclo->count(),
                    'evaluaciones_riesgo' => $evaluacionesRiesgoCiclo->count(),
                    'seguimientos' => $eventosSeguimientoCiclo->count(),
                    'riesgo_actual' => $evaluacionesRiesgoCiclo->where('es_actual', true)->sortByDesc('evaluado_at')->first()?->nivel_riesgo,
                    'puntaje_riesgo' => $evaluacionesRiesgoCiclo->where('es_actual', true)->sortByDesc('evaluado_at')->first()?->puntaje,
                ],
                'eventos' => $eventos->all(),
                'eventos_ocultos' => max(0, ($documentosCiclo->count() - 18) + ($bitacoraCiclo->count() - 20) + ($correccionesCiclo->count() - 12)),
            ];
        });
    }

    /** @return Collection<int, array<string, mixed>> */
    private function eventosCalificaciones(Collection $calificaciones): Collection
    {
        return $calificaciones
            ->groupBy(fn (Calificacion $item) => (string) ($item->periodo_id ?: 'sin-periodo'))
            ->map(function (Collection $grupo, string $periodoKey): array {
                /** @var Calificacion $primera */
                $primera = $grupo->first();
                $numericas = $grupo
                    ->filter(fn (Calificacion $item) => (bool) $item->es_numerica && $item->valor_numerico !== null)
                    ->map(fn (Calificacion $item) => (float) $item->valor_numerico);

                $fecha = $grupo
                    ->map(fn (Calificacion $item) => $item->fecha_captura ?? $item->updated_at ?? $item->created_at)
                    ->filter()
                    ->sortDesc()
                    ->first();

                $promedio = $numericas->isNotEmpty() ? number_format($numericas->avg(), 1) : null;
                $detalle = $grupo->count().' calificación(es) registrada(s)';
                if ($promedio !== null) {
                    $detalle .= ' · promedio '.$promedio;
                }

                return $this->evento(
                    'calificaciones-'.$periodoKey.'-'.$primera->inscripcion_ciclo_id,
                    'calificaciones',
                    $fecha,
                    'Evaluación: '.$this->etiquetaPeriodo($primera->periodo),
                    $detalle,
                    $grupo->whereNotNull('clave_especial')->pluck('clave_especial')->unique()->filter()->implode(', ') ?: null,
                    $grupo->sortByDesc('fecha_captura')->first()?->capturador?->name,
                    'chart-bar-square',
                    'blue'
                );
            })
            ->values();
    }

    /** @return array<int, array<string, string>> */
    private function detectarAlertas(Inscripcion $alumno, Collection $historiales, Collection $calificaciones, Collection $bitacora): array
    {
        $alertas = [];

        if ($historiales->where('estado', InscripcionCiclo::ESTADO_EN_CURSO)->count() > 1) {
            $alertas[] = [
                'nivel' => 'critico',
                'titulo' => 'Más de un ciclo en curso',
                'detalle' => 'El alumno tiene múltiples historiales marcados como vigentes.',
            ];
        }

        $reconstruidos = $historiales->filter(fn (InscripcionCiclo $item) => $item->reconstruido || $item->nivel_confianza !== 'exacto')->count();
        if ($reconstruidos > 0) {
            $alertas[] = [
                'nivel' => 'advertencia',
                'titulo' => 'Contextos reconstruidos',
                'detalle' => $reconstruidos.' ciclo(s) fueron reconstruidos o tienen una confianza distinta de exacta.',
            ];
        }

        $sinVinculo = $calificaciones->whereNull('inscripcion_ciclo_id')->count();
        if ($sinVinculo > 0) {
            $alertas[] = [
                'nivel' => 'advertencia',
                'titulo' => 'Calificaciones sin vínculo histórico',
                'detalle' => $sinVinculo.' calificación(es) todavía dependen solo del ciclo y la matrícula.',
            ];
        }

        $bitacoraSinVinculo = $bitacora->whereNull('inscripcion_ciclo_id')->count();
        if ($bitacoraSinVinculo > 0) {
            $alertas[] = [
                'nivel' => 'informativo',
                'titulo' => 'Bitácora heredada',
                'detalle' => $bitacoraSinVinculo.' registro(s) antiguos no tienen historial por ciclo asignado.',
            ];
        }

        if ($alumno->trashed()) {
            $alertas[] = [
                'nivel' => 'informativo',
                'titulo' => 'Expediente archivado',
                'detalle' => 'La trayectoria se conserva y puede consultarse aunque la matrícula esté archivada.',
            ];
        }

        return $alertas;
    }

    /** @return array<string, mixed> */
    private function datosAlumno(Inscripcion $alumno): array
    {
        $nombre = trim(implode(' ', array_filter([
            $alumno->apellido_paterno,
            $alumno->apellido_materno,
            $alumno->nombre,
        ])));

        $iniciales = collect(preg_split('/\s+/u', $nombre) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $parte) => Str::upper(Str::substr($parte, 0, 1)))
            ->implode('');

        $ubicacion = collect([
            $alumno->nivel?->nombre,
            $alumno->semestre?->numero ? $alumno->semestre->numero.'° semestre' : $alumno->grado?->nombre,
            $alumno->grupo?->asignacionGrupo?->nombre ? 'Grupo '.$alumno->grupo->asignacionGrupo->nombre : null,
        ])->filter()->implode(' · ');

        return [
            'id' => $alumno->id,
            'nombre' => $nombre ?: 'Alumno sin nombre',
            'iniciales' => $iniciales ?: 'AL',
            'foto_url' => ($alumno->foto_existe ?? false) ? $alumno->foto_url : null,
            'matricula' => $alumno->matricula ?: 'Sin matrícula',
            'folio' => $alumno->folio ?: '—',
            'curp' => $alumno->curp ?: '—',
            'genero' => $alumno->genero === 'H' ? 'Hombre' : ($alumno->genero === 'M' ? 'Mujer' : '—'),
            'fecha_nacimiento' => $this->fechaLegible($alumno->fecha_nacimiento),
            'edad' => $alumno->fecha_nacimiento ? $alumno->fecha_nacimiento->age : null,
            'estatus' => $alumno->trashed() ? 'archivado' : $alumno->estatusNormalizado(),
            'estatus_etiqueta' => $alumno->trashed() ? 'Archivado' : $alumno->etiqueta_estatus,
            'ubicacion_actual' => $ubicacion ?: 'Sin ubicación académica actual',
            'generacion_actual' => $alumno->generacion
                ? $alumno->generacion->anio_ingreso.'-'.$alumno->generacion->anio_egreso
                : '—',
            'ciclo_actual' => $alumno->cicloEscolar?->nombre ?? $alumno->ciclo?->ciclo ?? '—',
            'archivado' => $alumno->trashed(),
        ];
    }

    /** @return array<string, mixed> */
    private function cicloSinteticoActual(Inscripcion $alumno): array
    {
        $ciclo = $alumno->cicloEscolar?->nombre ?? $alumno->ciclo?->ciclo ?? 'Ubicación actual';
        $inicio = (int) ($alumno->cicloEscolar?->inicio_anio ?? 0);
        $fin = (int) ($alumno->cicloEscolar?->fin_anio ?? 0);

        return [
            'id' => 'actual-'.$alumno->id,
            'ciclo_escolar_id' => $alumno->ciclo_escolar_id,
            'ciclo' => $ciclo,
            'anio_inicio' => $inicio,
            'anio_fin' => $fin,
            'es_actual' => true,
            'estado' => 'en_curso',
            'estado_etiqueta' => 'En curso',
            'estatus' => $alumno->estatusNormalizado(),
            'estatus_etiqueta' => $alumno->etiqueta_estatus,
            'resultado' => null,
            'resultado_etiqueta' => 'Ubicación actual',
            'tono' => 'sky',
            'nivel' => $alumno->nivel?->nombre,
            'grado' => $alumno->grado?->nombre,
            'semestre' => $alumno->semestre?->numero ? $alumno->semestre->numero.'° semestre' : null,
            'grupo' => $alumno->grupo?->asignacionGrupo?->nombre,
            'generacion' => $alumno->generacion
                ? $alumno->generacion->anio_ingreso.'-'.$alumno->generacion->anio_egreso
                : null,
            'ubicacion' => $this->ubicacionDesdeModelo($alumno),
            'fecha_ingreso' => $this->fechaLegible($alumno->fecha_inscripcion ?? $alumno->created_at),
            'fecha_salida' => null,
            'matricula' => $alumno->matricula,
            'reconstruido' => true,
            'nivel_confianza' => 'inferido',
            'asignaciones' => [],
            'estadisticas' => [
                'calificaciones' => 0,
                'periodos' => 0,
                'promedio' => '—',
                'documentos' => 0,
                'movimientos' => 0,
                'correcciones' => 0,
                'evaluaciones_riesgo' => 0,
                'seguimientos' => 0,
                'riesgo_actual' => null,
                'puntaje_riesgo' => null,
            ],
            'eventos' => [
                $this->evento(
                    'actual-'.$alumno->id,
                    'ingreso',
                    $alumno->fecha_inscripcion ?? $alumno->created_at,
                    'Registro institucional actual',
                    $this->ubicacionDesdeModelo($alumno),
                    'Contexto temporal generado desde la matrícula actual.',
                    null,
                    'academic-cap',
                    'sky'
                ),
            ],
            'eventos_ocultos' => 0,
        ];
    }

    private function tonoRiesgo(string $nivel): string
    {
        return match ($nivel) {
            'critico' => 'rose',
            'alto' => 'orange',
            'moderado' => 'amber',
            default => 'emerald',
        };
    }

    private function tonoSeguimiento(string $tipo): string
    {
        return match ($tipo) {
            'cierre', 'mejora' => 'emerald',
            'reapertura' => 'rose',
            'plan', 'accion' => 'indigo',
            'accion_actualizada' => 'violet',
            default => 'blue',
        };
    }

    private function iconoSeguimiento(string $tipo): string
    {
        return match ($tipo) {
            'cierre', 'mejora' => 'check-circle',
            'reapertura' => 'arrow-uturn-left',
            'plan', 'accion', 'accion_actualizada' => 'clipboard-document-check',
            default => 'history',
        };
    }

    private function perteneceAlCiclo(Model $modelo, InscripcionCiclo $historial): bool
    {
        $historialId = $modelo->getAttribute('inscripcion_ciclo_id');
        if ($historialId !== null) {
            return (int) $historialId === (int) $historial->id;
        }

        $cicloId = $modelo->getAttribute('ciclo_escolar_id');

        return $cicloId !== null && (int) $cicloId === (int) $historial->ciclo_escolar_id;
    }

    private function fechaDentroDelCiclo(mixed $fecha, InscripcionCiclo $historial): bool
    {
        $fecha = $this->carbon($fecha);
        if (! $fecha) {
            return false;
        }

        $inicio = $historial->cicloEscolar?->inicio_anio
            ? Carbon::create((int) $historial->cicloEscolar->inicio_anio, 7, 1)->startOfDay()
            : $this->carbon($historial->fecha_ingreso);
        $fin = $historial->cicloEscolar?->fin_anio
            ? Carbon::create((int) $historial->cicloEscolar->fin_anio, 9, 30)->endOfDay()
            : $this->carbon($historial->fecha_salida);

        if (! $inicio || ! $fin) {
            return false;
        }

        return $fecha->betweenIncluded($inicio, $fin);
    }

    /** @return array<string, mixed> */
    private function evento(
        string $id,
        string $tipo,
        mixed $fecha,
        string $titulo,
        ?string $descripcion,
        ?string $detalle,
        ?string $actor,
        string $icono,
        string $tono
    ): array {
        $carbon = $this->carbon($fecha) ?? now();

        return [
            'id' => $id,
            'tipo' => $tipo,
            'fecha' => $carbon->translatedFormat('d M Y'),
            'fecha_completa' => $carbon->format('d/m/Y H:i'),
            'fecha_iso' => $carbon->toIso8601String(),
            'timestamp' => $carbon->getTimestamp(),
            'titulo' => $titulo,
            'descripcion' => $descripcion ?: 'Sin detalle adicional.',
            'detalle' => $detalle,
            'actor' => $actor,
            'icono' => $icono,
            'tono' => $tono,
        ];
    }

    private function ubicacionDesdeModelo(Model $modelo): string
    {
        $nivel = $modelo->getRelationValue('nivel')?->nombre;
        $grado = $modelo->getRelationValue('grado')?->nombre;
        $semestre = $modelo->getRelationValue('semestre')?->numero;
        $grupo = $modelo->getRelationValue('grupo')?->asignacionGrupo?->nombre;

        return collect([
            $nivel,
            $semestre ? $semestre.'° semestre' : $grado,
            $grupo ? 'Grupo '.$grupo : null,
        ])->filter()->implode(' · ') ?: 'Ubicación académica no disponible';
    }

    private function descripcionProyeccion(ProyeccionContinuidad $proyeccion): string
    {
        return collect([
            $proyeccion->cicloDestino?->nombre,
            $proyeccion->nivelDestino?->nombre,
            $proyeccion->semestreDestino?->numero
                ? $proyeccion->semestreDestino->numero.'° semestre'
                : $proyeccion->gradoDestino?->nombre,
            $proyeccion->grupoDestino?->asignacionGrupo?->nombre
                ? 'Grupo '.$proyeccion->grupoDestino->asignacionGrupo->nombre
                : null,
        ])->filter()->implode(' · ') ?: 'Destino académico pendiente de definir';
    }

    private function descripcionDocumento(DocumentoAlumno $documento): string
    {
        $partes = [
            $documento->estado ? 'Estado: '.$this->etiqueta((string) $documento->estado) : null,
            $documento->version ? 'Versión '.$documento->version : null,
            $documento->paginas_total ? $documento->paginas_total.' página(s)' : null,
        ];

        return collect($partes)->filter()->implode(' · ') ?: ($documento->nombre_original ?: 'Documento registrado');
    }

    private function etiquetaPeriodo(?Model $periodo): string
    {
        if (! $periodo) {
            return 'Periodo sin identificar';
        }

        $basica = $periodo->getRelationValue('periodoBasica');
        $mesBasica = $periodo->getRelationValue('mesesBasica');
        $mesBachillerato = $periodo->getRelationValue('mesesBachillerato');
        $parcial = $periodo->getRelationValue('parcialBachillerato');

        if ($parcial || $mesBachillerato) {
            return collect([
                $parcial?->parcial ? 'Parcial '.$parcial->parcial : null,
                $mesBachillerato?->meses,
            ])->filter()->implode(' · ');
        }

        return collect([
            $basica?->periodo ? 'Periodo '.$basica->periodo : null,
            $mesBasica?->meses,
        ])->filter()->implode(' · ') ?: 'Periodo académico';
    }

    private function tituloAsignacion(string $tipo): string
    {
        return match ($tipo) {
            'cambio_grupo' => 'Cambio de grupo',
            'cambio_grado' => 'Cambio de grado',
            'cambio_semestre' => 'Cambio de semestre',
            'reingreso' => 'Reasignación por reingreso',
            'reconstruccion' => 'Ubicación histórica reconstruida',
            default => 'Actualización de ubicación académica',
        };
    }

    private function tituloMovimiento(string $tipo): string
    {
        return match ($tipo) {
            'baja_temporal' => 'Baja temporal registrada',
            'baja_definitiva' => 'Baja definitiva registrada',
            'traslado', 'trasladado' => 'Traslado a otra institución',
            'reingreso' => 'Reingreso registrado',
            'reincorporacion' => 'Reincorporación registrada',
            'promocion', 'promovido' => 'Promoción académica',
            'egreso', 'egresado' => 'Egreso del nivel',
            default => 'Movimiento administrativo: '.$this->etiqueta($tipo),
        };
    }

    private function tonoMovimiento(string $tipo): string
    {
        return match ($tipo) {
            'baja_definitiva' => 'rose',
            'baja_temporal' => 'orange',
            'traslado', 'trasladado' => 'violet',
            'egreso', 'egresado', 'promocion', 'promovido', 'reingreso', 'reincorporacion' => 'emerald',
            default => 'slate',
        };
    }

    private function tituloCambioAcademico(string $tipo): string
    {
        return match ($tipo) {
            'cambio_grupo' => 'Cambio de grupo registrado',
            'cambio_grado' => 'Cambio de grado registrado',
            'cambio_semestre' => 'Cambio de semestre registrado',
            'cambio_generacion' => 'Cambio de generación registrado',
            'correccion_contexto' => 'Corrección del contexto académico',
            default => 'Cambio académico: '.$this->etiqueta($tipo),
        };
    }

    private function tituloBitacora(string $accion): string
    {
        return match ($accion) {
            'crear', 'creada' => 'Calificación registrada',
            'actualizar', 'editar', 'modificada' => 'Calificación modificada',
            'eliminar', 'eliminada' => 'Calificación eliminada',
            'importar', 'importada' => 'Calificación importada',
            default => 'Movimiento en calificaciones',
        };
    }

    private function etiquetaResultado(string $resultado): string
    {
        return match ($resultado) {
            'promovido', 'promovido_grado' => 'Promovido al siguiente grado',
            'promovido_nivel' => 'Promovido al siguiente nivel',
            'no_promovido' => 'No promovido',
            'egresado' => 'Egresado',
            'traslado', 'trasladado' => 'Trasladado',
            'baja_temporal' => 'Baja temporal',
            'baja_definitiva' => 'Baja definitiva',
            'no_reinscrito' => 'No reinscrito',
            default => $this->etiqueta($resultado),
        };
    }

    private function tonoResultado(string $resultado): string
    {
        return match ($resultado) {
            'promovido', 'promovido_grado', 'promovido_nivel', 'egresado' => 'emerald',
            'no_promovido', 'baja_temporal' => 'orange',
            'baja_definitiva' => 'rose',
            'traslado', 'trasladado' => 'violet',
            'no_reinscrito' => 'slate',
            default => 'blue',
        };
    }

    private function descripcionCambioEstado(mixed $anterior, mixed $nuevo): ?string
    {
        $anterior = is_array($anterior) ? $anterior : [];
        $nuevo = is_array($nuevo) ? $nuevo : [];

        $campos = [
            'estatus' => 'Estatus',
            'nivel_id' => 'Nivel',
            'grado_id' => 'Grado',
            'grupo_id' => 'Grupo',
            'semestre_id' => 'Semestre',
            'generacion_id' => 'Generación',
        ];

        $cambios = [];
        foreach ($campos as $campo => $etiqueta) {
            if (($anterior[$campo] ?? null) !== ($nuevo[$campo] ?? null)) {
                $cambios[] = $etiqueta.' actualizado';
            }
        }

        return $cambios !== [] ? implode(' · ', $cambios) : null;
    }

    private function valorCorreccion(mixed $valor): string
    {
        if (! is_array($valor)) {
            return filled($valor) ? (string) $valor : '—';
        }

        foreach (['calificacion', 'valor_numerico', 'clave_especial', 'valor'] as $clave) {
            if (array_key_exists($clave, $valor) && filled($valor[$clave])) {
                return (string) $valor[$clave];
            }
        }

        return '—';
    }

    private function etiqueta(string $valor): string
    {
        return Str::of($valor)->replace(['_', '-'], ' ')->squish()->title()->toString();
    }

    private function fechaLegible(mixed $fecha): ?string
    {
        return $this->carbon($fecha)?->translatedFormat('d M Y');
    }

    private function carbon(mixed $fecha): ?CarbonInterface
    {
        if ($fecha instanceof CarbonInterface) {
            return $fecha;
        }

        if (blank($fecha)) {
            return null;
        }

        try {
            return Carbon::parse($fecha);
        } catch (\Throwable) {
            return null;
        }
    }
}
