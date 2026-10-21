<?php

namespace App\Livewire\Academico;

use App\Exports\CalificacionesDocenteHoja;
use App\Exports\CalificacionesDocenteLibro;
use App\Imports\CalificacionesDocenteLectura;
use App\Models\AsignacionMateria;
use App\Models\BitacoraCalificacion;
use App\Models\Calificacion;
use App\Models\CalificacionDocenteCaptura;
use App\Models\CicloEscolar;
use App\Models\Nivel;
use App\Models\Periodos;
use App\Services\CicloNivelGateService;
use App\Services\ListaAcademicaService;
use App\Support\CalificacionBachillerato;
use App\Support\ReglasMateriaBachillerato;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Interfaz administrativa independiente de la captura general.
 * No da permisos docentes y NO usa las hojas Excel como fuente de autorización.
 */
class CalificacionesPorDocente extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $slug_nivel;
    #[Locked]
    public int $nivel_id;

    public string $ciclo_escolar_id = '';
    public string $profesor_id = '';
    public string $evaluacion_id = '';
    public string $asignacion_id = '';
    public string $tipo_plantilla = 'profesor';
    public string $motivo = '';
    public array $valores = [];
    public array $preview = [];
    public array $aplicar = [];
    public array $erroresImportacion = [];
    public bool $mostrarPreview = false;
    public $archivoExcel;

    public function mount(string $slug_nivel): void
    {
        $this->autorizar();
        abort_unless(in_array($slug_nivel, ['primaria', 'secundaria', 'bachillerato'], true), 404);
        $this->slug_nivel = $slug_nivel;
        $this->nivel_id = (int) Nivel::query()->where('slug', $slug_nivel)->firstOrFail()->id;
        $actual = CicloEscolar::query()->where('es_actual', true)->whereNull('cerrado_at')->latest('id')->first();
        $this->ciclo_escolar_id = (string) ($actual?->id ?? '');
    }

    private function autorizar(): void
    {
        abort_unless(auth()->check() && (bool) auth()->user()->is_admin && auth()->user()->canAccess('calificaciones.capturar'), 403);
    }

    private function ciclo(): ?CicloEscolar
    {
        return CicloEscolar::query()->find((int) $this->ciclo_escolar_id);
    }

    private function editable(): bool
    {
        $ciclo = $this->ciclo();
        return (bool) ($ciclo?->es_actual) && blank($ciclo?->cerrado_at);
    }

    /** Cada asignación pertenece obligatoriamente al docente, ciclo y nivel seleccionados. */
    private function asignaciones()
    {
        return AsignacionMateria::query()
            ->with(['materia', 'profesor', 'grupo.asignacionGrupo', 'grado', 'semestre', 'generacion'])
            ->where('nivel_id', $this->nivel_id)
            ->where('ciclo_escolar_id', (int) $this->ciclo_escolar_id)
            ->where('profesor_id', (int) $this->profesor_id)
            ->confirmadas()
            ->whereHas('materia', function ($query): void {
                $query->where('nivel_id', $this->nivel_id);
                if ($this->slug_nivel === 'bachillerato') {
                    ReglasMateriaBachillerato::aplicarCapturables($query, '');
                } else {
                    $query->where('calificable', true)->whereNull('semestre_id');
                }
            })
            ->orderBy('grupo_id')->orderBy('orden')->orderBy('id')->get();
    }

    private function asignacion(int $id): AsignacionMateria
    {
        $item = $this->asignaciones()->firstWhere('id', $id);
        if (! $item) {
            throw ValidationException::withMessages(['asignacion_id' => 'Esta materia no pertenece al docente y ciclo seleccionados.']);
        }
        return $item;
    }

    private function periodo(AsignacionMateria $asignacion): Periodos
    {
        $query = Periodos::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('ciclo_escolar_id', (int) $this->ciclo_escolar_id);
        if ($this->slug_nivel === 'bachillerato') {
            $query->where('generacion_id', (int) $asignacion->generacion_id)
                ->where('semestre_id', (int) $asignacion->semestre_id)
                ->where('parcial_bachillerato_id', (int) $this->evaluacion_id);
        } else {
            $query->where('periodo_basica_id', (int) $this->evaluacion_id);
        }
        $periodo = $query->latest('id')->first();
        if (! $periodo) {
            throw ValidationException::withMessages(['evaluacion_id' => 'No existe un periodo configurado para esta materia y evaluación.']);
        }
        return $periodo;
    }

    private function alumnos(AsignacionMateria $asignacion, Periodos $periodo): array
    {
        $ciclo = $this->ciclo();
        $alumnos = app(ListaAcademicaService::class)->alumnosPorContexto(
            cicloEscolarId: (int) $asignacion->ciclo_escolar_id,
            grupoIds: [(int) $asignacion->grupo_id],
            fechaCorte: $periodo->fecha_inicio ?? now()->toDateString(),
            nivelId: (int) $asignacion->nivel_id,
            gradoId: (int) $asignacion->grado_id,
            generacionId: (int) $asignacion->generacion_id,
            semestreId: $this->slug_nivel === 'bachillerato' ? (int) $asignacion->semestre_id : null,
            usarHistorialCiclo: true,
            incluirNoActivos: false,
            fechaInicio: $periodo->fecha_inicio,
            fechaFin: $periodo->fecha_fin,
            periodoId: (int) $periodo->id,
            usarActualComoRespaldo: (bool) ($ciclo?->es_actual && blank($ciclo?->cerrado_at)),
            incluirTodaGeneracionBachillerato: $this->slug_nivel === 'bachillerato',
        );
        // No inventar una inscripción_ciclo para contextos históricos inferidos.
        return $alumnos->mapWithKeys(function ($a): array {
            return [(int) $a->id => [
                'id' => (int) $a->id,
                'inscripcion_ciclo_id' => $a->getAttribute('inscripcion_ciclo_id') ? (int) $a->getAttribute('inscripcion_ciclo_id') : null,
                'matricula' => (string) $a->matricula,
                'nombre' => trim(($a->apellido_paterno ?? '').' '.($a->apellido_materno ?? '').' '.($a->nombre ?? '')),
                'pendiente' => (bool) $a->getAttribute('asignacion_contexto_pendiente'),
            ]];
        })->all();
    }

    private function calificacionesActuales(AsignacionMateria $asignacion, Periodos $periodo): array
    {
        return Calificacion::query()
            ->where('asignacion_materia_id', $asignacion->id)
            ->where('periodo_id', $periodo->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->get()->keyBy('inscripcion_id')->all();
    }

    private function contexto(int $asignacionId): array
    {
        $asignacion = $this->asignacion($asignacionId);
        $periodo = $this->periodo($asignacion);
        return [$asignacion, $periodo, $this->alumnos($asignacion, $periodo), $this->calificacionesActuales($asignacion, $periodo)];
    }

    private function normalizar(mixed $valor): ?string
    {
        $v = strtoupper(trim((string) ($valor ?? '')));
        if ($v === '') return null;
        if (in_array($v, ['AC', 'ED', 'RA', 'NP', 'SD'], true)) return $v;
        if (! is_numeric($v) || ! is_finite((float) $v) || (float) $v < 0 || (float) $v > 10) return 'INVALIDO';
        if ($this->slug_nivel === 'bachillerato') return (string) CalificacionBachillerato::truncarParcial($v);
        return (string) (float) $v;
    }

    public function updatedCicloEscolarId(): void { $this->profesor_id = ''; $this->reiniciarSeleccion(); }
    public function updatedProfesorId(): void { $this->reiniciarSeleccion(); }
    public function updatedEvaluacionId(): void { $this->asignacion_id = ''; $this->limpiarTrabajo(); }
    public function updatedAsignacionId(): void { $this->cargarMateria(); }

    private function reiniciarSeleccion(): void
    {
        $this->evaluacion_id = ''; $this->asignacion_id = ''; $this->limpiarTrabajo();
    }

    private function limpiarTrabajo(): void
    {
        $this->valores = []; $this->preview = []; $this->aplicar = [];
        $this->erroresImportacion = []; $this->mostrarPreview = false; $this->archivoExcel = null;
    }

    public function cargarMateria(): void
    {
        $this->autorizar(); $this->limpiarTrabajo();
        if (! $this->asignacion_id || ! $this->evaluacion_id) return;
        [$asignacion, $periodo, $alumnos, $actuales] = $this->contexto((int) $this->asignacion_id);
        $borrador = CalificacionDocenteCaptura::query()->where('asignacion_materia_id', $asignacion->id)->where('periodo_id', $periodo->id)->first();
        foreach ($alumnos as $id => $alumno) {
            $this->valores[$id] = (string) ($borrador?->valores[$id] ?? ($actuales[$id] ?? null)?->calificacion ?? '');
        }
    }

    public function guardarBorrador(): void
    {
        $this->autorizar(); $this->asegurarEditable();
        [$asignacion, $periodo, $alumnos] = $this->contexto((int) $this->asignacion_id);
        $this->asegurarSinCierre($asignacion, $periodo);
        $valores = [];
        foreach ($alumnos as $id => $a) {
            if ($a['pendiente']) continue;
            $v = $this->normalizar($this->valores[$id] ?? null);
            if ($v === 'INVALIDO') throw ValidationException::withMessages(['valores.'.$id => 'La calificación de '.$a['nombre'].' está fuera de rango.']);
            if ($v !== null) $valores[$id] = $v;
        }
        CalificacionDocenteCaptura::query()->updateOrCreate(
            ['asignacion_materia_id' => $asignacion->id, 'periodo_id' => $periodo->id],
            ['valores' => $valores, 'estado' => 'borrador', 'actualizado_por' => auth()->id(), 'finalizado_at' => null]
        );
        $this->dispatch('swal', ['title' => 'Borrador guardado sin modificar las boletas.', 'icon' => 'success']);
    }

    public function revisarCaptura(): void
    {
        $this->autorizar(); $this->asegurarEditable();
        [$asignacion, $periodo, $alumnos, $actuales] = $this->contexto((int) $this->asignacion_id);
        $this->asegurarSinCierre($asignacion, $periodo);
        $rows = [];
        foreach ($alumnos as $id => $a) {
            if ($a['pendiente']) continue;
            $v = $this->normalizar($this->valores[$id] ?? null);
            if ($v === null) continue; // Vacío significa no modificar, NUNCA borrar.
            if ($v === 'INVALIDO') throw ValidationException::withMessages(['valores.'.$id => 'Calificación inválida para '.$a['nombre']]);
            $rows[] = ['asignacion_id' => $asignacion->id, 'periodo_id' => $periodo->id,
                'inscripcion_id' => $id, 'matricula' => $a['matricula'], 'alumno' => $a['nombre'],
                'anterior' => ($actuales[$id] ?? null)?->calificacion, 'nuevo' => $v];
        }
        $this->prepararPreview($rows);
        CalificacionDocenteCaptura::query()->updateOrCreate(
            ['asignacion_materia_id' => $asignacion->id, 'periodo_id' => $periodo->id],
            ['valores' => array_filter($this->valores, fn ($v) => trim((string) $v) !== ''), 'estado' => 'revision', 'actualizado_por' => auth()->id(), 'finalizado_at' => null]
        );
    }

    private function prepararPreview(array $rows): void
    {
        $this->preview = []; $this->aplicar = [];
        foreach ($rows as $row) {
            if ((string) ($row['anterior'] ?? '') === (string) $row['nuevo']) continue;
            $indice = count($this->preview);
            $this->preview[] = $row;
            $this->aplicar[$indice] = ! isset($row['aviso']);
        }
        $this->mostrarPreview = ! empty($this->preview);
        if (! $this->mostrarPreview) $this->addError('preview', 'No se encontraron calificaciones nuevas o modificadas.');
    }

    public function descargarExcel()
    {
        $this->autorizar();
        if (! $this->profesor_id || ! $this->evaluacion_id) {
            throw ValidationException::withMessages(['evaluacion_id' => 'Selecciona un profesor y evaluación.']);
        }
        $asignaciones = $this->tipo_plantilla === 'materia'
            ? collect([$this->asignacion((int) $this->asignacion_id)])
            : $this->asignaciones();
        if ($asignaciones->isEmpty()) throw ValidationException::withMessages(['profesor_id' => 'El docente no tiene materias asignadas.']);
        $hojas = [];
        $matriz = [];
        foreach ($asignaciones as $asignacion) {
            [$a, $periodo, $alumnos, $actuales] = $this->contexto((int) $asignacion->id);
            $filas = [];
            foreach ($alumnos as $alumno) {
                $filas[] = [$a->id, $periodo->id, $alumno['matricula'], $alumno['nombre'], (string) (($actuales[$alumno['id']] ?? null)?->calificacion ?? ''), ''];
            }
            if ($this->tipo_plantilla === 'matriz') {
                $matriz = array_merge($matriz, $filas);
            } else {
                $hojas[] = new CalificacionesDocenteHoja('M'.$a->id.'-'.($a->materia?->materia ?? 'Materia'), $filas);
            }
        }
        if ($this->tipo_plantilla === 'matriz') $hojas[] = new CalificacionesDocenteHoja('Matriz docente', $matriz);
        $nombre = 'CALIFICACIONES_DOCENTE_'.$this->slug_nivel.'_C'.$this->ciclo_escolar_id.'_P'.$this->profesor_id.'_E'.$this->evaluacion_id.'.xlsx';
        return Excel::download(new CalificacionesDocenteLibro($hojas), $nombre);
    }

    public function analizarExcel(): void
    {
        $this->autorizar(); $this->asegurarEditable(); $this->limpiarPreviewSolo();
        $this->validate(['archivoExcel' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240']]);
        if (! $this->profesor_id || ! $this->evaluacion_id) throw ValidationException::withMessages(['profesor_id' => 'Selecciona primero un docente y una evaluación.']);
        $hojas = Excel::toArray(new CalificacionesDocenteLectura(), $this->archivoExcel);
        $contextos = []; $filas = []; $vistas = []; $total = 0;
        foreach ($hojas as $numeroHoja => $hoja) {
            if (array_slice(array_map(fn($v) => strtoupper(trim((string) $v)), $hoja[0] ?? []), 0, 6)
                !== ['ASIGNACION_ID', 'PERIODO_ID', 'MATRICULA', 'ALUMNO', 'ACTUAL', 'NUEVA']) {
                $this->erroresImportacion[] = 'Hoja '.($numeroHoja + 1).': encabezado diferente a la plantilla oficial.';
                continue;
            }
            foreach (array_slice($hoja, 1) as $pos => $fila) {
                if (++$total > 5000) throw ValidationException::withMessages(['archivoExcel' => 'Máximo 5000 registros por archivo.']);
                $nuevoRaw = trim((string) ($fila[5] ?? ''));
                if ($nuevoRaw === '') continue; // no borrar por blancos
                $id = filter_var($fila[0] ?? null, FILTER_VALIDATE_INT);
                $periodoId = filter_var($fila[1] ?? null, FILTER_VALIDATE_INT);
                if (! $id || ! $periodoId) { $this->erroresImportacion[] = 'Hoja '.($numeroHoja + 1).' fila '.($pos + 2).': IDs inválidos.'; continue; }
                try {
                    if (! isset($contextos[$id])) $contextos[$id] = $this->contexto((int) $id);
                    [$asignacion, $periodo, $alumnos, $actuales] = $contextos[$id];
                    $matricula = trim((string) ($fila[2] ?? ''));
                    $alumno = collect($alumnos)->first(fn ($a) => $a['matricula'] === $matricula);
                    if ($periodo->id !== (int) $periodoId || ! $alumno || $alumno['pendiente']) throw new \RuntimeException('Periodo o matrícula ajenos al docente/grupo, o historial pendiente de validación.');
                    $valor = $this->normalizar($nuevoRaw);
                    $aviso = null;
                    if ($valor === 'INVALIDO') {
                        $aviso = 'Calificación inválida: corrígela en la vista previa antes de seleccionar este registro.';
                        $valor = $nuevoRaw;
                    }
                    $clave = $id.'-'.$periodo->id.'-'.$alumno['id'];
                    if (isset($vistas[$clave])) throw new \RuntimeException('Matrícula duplicada en el archivo.');
                    $vistas[$clave] = true;
                    $filas[] = ['asignacion_id' => (int) $id, 'periodo_id' => (int) $periodoId,
                        'inscripcion_id' => $alumno['id'], 'matricula' => $matricula, 'alumno' => $alumno['nombre'],
                        'anterior' => ($actuales[$alumno['id']] ?? null)?->calificacion, 'nuevo' => $valor,
                        'aviso' => $aviso];
                } catch (\Throwable $e) {
                    $this->erroresImportacion[] = 'Hoja '.($numeroHoja + 1).' fila '.($pos + 2).': '.$e->getMessage();
                }
            }
        }
        $this->prepararPreview($filas);
    }

    public function descargarIncidencias()
    {
        $this->autorizar();
        if (! $this->erroresImportacion) {
            throw ValidationException::withMessages(['archivoExcel' => 'No hay incidencias para descargar.']);
        }
        $errores = $this->erroresImportacion;
        return response()->streamDownload(function () use ($errores): void {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, ['INCIDENCIAS_IMPORTACION']);
            foreach ($errores as $e) fputcsv($salida, [$e]);
            fclose($salida);
        }, 'incidencias_calificaciones_'.$this->slug_nivel.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function limpiarPreviewSolo(): void
    {
        $this->preview = []; $this->aplicar = []; $this->erroresImportacion = []; $this->mostrarPreview = false;
    }

    private function asegurarEditable(): void
    {
        if (! $this->editable()) throw ValidationException::withMessages(['ciclo_escolar_id' => 'Los ciclos históricos y cerrados se corrigen únicamente desde la captura general con autorización y bitácora.']);
        app(CicloNivelGateService::class)->asegurar((int) $this->ciclo_escolar_id, $this->nivel_id, 'calificaciones');
    }

    private function asegurarSinCierre(AsignacionMateria $a, Periodos $p): void
    {
        if ($a->estado !== AsignacionMateria::ESTADO_ACTIVA) throw ValidationException::withMessages(['asignacion_id' => 'La materia no está activa para captura.']);
        $estado = CalificacionDocenteCaptura::query()->where('asignacion_materia_id', $a->id)->where('periodo_id', $p->id)->first();
        if ($estado?->estado === 'finalizado') throw ValidationException::withMessages(['preview' => 'Esta materia ya fue finalizada. Debes reabrirla mediante una acción administrativa justificada.']);
    }

    public function reabrirMateria(): void
    {
        $this->autorizar(); $this->asegurarEditable();
        $this->validate(['motivo' => ['required', 'string', 'min:10', 'max:500']]);
        [$a, $p] = $this->contexto((int) $this->asignacion_id);
        $captura = CalificacionDocenteCaptura::query()->where('asignacion_materia_id', $a->id)->where('periodo_id', $p->id)->firstOrFail();
        abort_unless($captura->estado === 'finalizado', 422);
        DB::transaction(function () use ($captura, $a, $p): void {
            $captura->update(['estado' => 'revision', 'finalizado_at' => null, 'actualizado_por' => auth()->id()]);
            // Registro auditado aun si la reapertura no cambia una calificación.
            BitacoraCalificacion::query()->create([
                'nivel_id' => $this->nivel_id, 'grado_id' => $a->grado_id, 'grupo_id' => $a->grupo_id,
                'generacion_id' => $a->generacion_id, 'semestre_id' => $a->semestre_id,
                'ciclo_escolar_id' => $a->ciclo_escolar_id, 'periodo_id' => $p->id,
                'asignacion_materia_id' => $a->id, 'user_id' => auth()->id(),
                'accion' => 'editar', 'tipo_valor' => 'vacio', 'observacion' => 'REAPERTURA_DOCENTE_PRO', 'motivo' => $this->motivo, 'ip' => request()->ip(),
            ]);
        });
        $this->motivo = '';
        $this->cargarMateria();
        $this->dispatch('swal', ['title' => 'Captura reabierta y auditada.', 'icon' => 'success']);
    }

    /** Confirmación explícita, revalidación en BD y operación atómica por lote. */
    public function confirmar(): void
    {
        $this->autorizar(); $this->asegurarEditable();
        $this->validate(['motivo' => ['required', 'string', 'min:10', 'max:500']]);
        $seleccionadas = collect($this->preview)->filter(fn ($row, $i) => (bool) ($this->aplicar[$i] ?? false))->values()->all();
        if (! $seleccionadas) throw ValidationException::withMessages(['preview' => 'Selecciona al menos un registro para guardar.']);
        DB::transaction(function () use ($seleccionadas): void {
            $contextos = []; $afectadas = [];
            foreach ($seleccionadas as $row) {
                $id = (int) $row['asignacion_id'];
                if (! isset($contextos[$id])) {
                    $contextos[$id] = $this->contexto($id);
                    $this->asegurarSinCierre($contextos[$id][0], $contextos[$id][1]);
                }
                [$a, $p, $alumnos] = $contextos[$id];
                $studentId = (int) $row['inscripcion_id'];
                $alumno = $alumnos[$studentId] ?? null;
                if (! $alumno || $alumno['pendiente'] || (int) $row['periodo_id'] !== $p->id || $alumno['matricula'] !== $row['matricula']) {
                    throw ValidationException::withMessages(['preview' => 'El alumno, materia o periodo cambió. Vuelve a analizar la captura.']);
                }
                $nuevo = $this->normalizar($row['nuevo']);
                if ($nuevo === null || $nuevo === 'INVALIDO') throw ValidationException::withMessages(['preview' => 'Hay una calificación inválida.']);
                $anterior = Calificacion::query()->where('periodo_id', $p->id)
                    ->where('asignacion_materia_id', $a->id)->where('inscripcion_id', $studentId)->lockForUpdate()->first();
                if ((string) ($anterior?->calificacion ?? '') !== (string) ($row['anterior'] ?? '')) {
                    throw ValidationException::withMessages(['preview' => 'Otra sesión modificó una calificación. Actualiza la vista previa antes de confirmar.']);
                }
                Calificacion::query()->updateOrCreate(
                    ['periodo_id' => $p->id, 'asignacion_materia_id' => $a->id, 'inscripcion_id' => $studentId],
                    ['inscripcion_ciclo_id' => $alumno['inscripcion_ciclo_id'], 'nivel_id' => $this->nivel_id,
                        'grado_id' => $a->grado_id, 'grupo_id' => $a->grupo_id, 'generacion_id' => $a->generacion_id,
                        'semestre_id' => $this->slug_nivel === 'bachillerato' ? $a->semestre_id : null,
                        'ciclo_escolar_id' => $a->ciclo_escolar_id, 'calificacion' => $nuevo,
                        'valor_numerico' => is_numeric($nuevo) ? (float) $nuevo : null,
                        'es_numerica' => is_numeric($nuevo), 'clave_especial' => is_numeric($nuevo) ? null : $nuevo,
                        'capturado_por' => auth()->id(), 'fecha_captura' => now(), 'ip_captura' => request()->ip()]
                );
                BitacoraCalificacion::query()->create([
                    'nivel_id' => $this->nivel_id, 'grado_id' => $a->grado_id, 'grupo_id' => $a->grupo_id,
                    'generacion_id' => $a->generacion_id, 'semestre_id' => $a->semestre_id,
                    'ciclo_escolar_id' => $a->ciclo_escolar_id, 'periodo_id' => $p->id,
                    'inscripcion_id' => $studentId, 'inscripcion_ciclo_id' => $alumno['inscripcion_ciclo_id'],
                    'asignacion_materia_id' => $a->id, 'user_id' => auth()->id(),
                    'accion' => $anterior ? 'editar' : 'crear', 'calificacion_anterior' => $anterior?->calificacion,
                    'calificacion_nueva' => $nuevo, 'valor_anterior_numerico' => is_numeric($anterior?->calificacion) ? (float) $anterior->calificacion : null,
                    'valor_nuevo_numerico' => is_numeric($nuevo) ? (float) $nuevo : null,
                    'tipo_valor' => is_numeric($nuevo) ? 'numerico' : 'especial',
                    'motivo' => $this->motivo, 'ip' => request()->ip(),
                ]);
                $afectadas[$id] = $p->id;
            }
            foreach ($afectadas as $aId => $pId) {
                [$a, $p, $alumnos] = $contextos[$aId];
                $idsAlumnos = collect($alumnos)->reject(fn ($alumno) => $alumno['pendiente'])->keys()->all();
                $total = count($idsAlumnos);
                $completos = $total > 0 && $total === count($alumnos) && Calificacion::query()
                    ->where('asignacion_materia_id', $aId)->where('periodo_id', $pId)
                    ->whereIn('inscripcion_id', $idsAlumnos)->whereNotNull('calificacion')->count() === $total;
                CalificacionDocenteCaptura::query()->updateOrCreate(
                    ['asignacion_materia_id' => $aId, 'periodo_id' => $pId],
                    ['estado' => $completos ? 'finalizado' : 'revision', 'valores' => null,
                     'actualizado_por' => auth()->id(), 'finalizado_at' => $completos ? now() : null]
                );
            }
        }, 3);
        $cantidad = count($seleccionadas);
        $this->limpiarTrabajo(); $this->motivo = '';
        if ($this->asignacion_id) $this->cargarMateria();
        $this->dispatch('swal', ['title' => $cantidad.' calificaciones confirmadas.', 'icon' => 'success']);
    }

    public function render()
    {
        $this->autorizar();
        $ciclos = CicloEscolar::query()->orderByDesc('inicio_anio')->get();
        $profesores = $this->ciclo_escolar_id
            ? \App\Models\Persona::query()->whereIn('id', AsignacionMateria::query()->where('ciclo_escolar_id', (int) $this->ciclo_escolar_id)
                ->where('nivel_id', $this->nivel_id)->confirmadas()->whereNotNull('profesor_id')->select('profesor_id'))
                ->orderBy('apellido_paterno')->orderBy('apellido_materno')->get()
            : collect();
        $asignaciones = $this->profesor_id && $this->ciclo_escolar_id ? $this->asignaciones() : collect();
        $evaluaciones = $this->ciclo_escolar_id
            ? Periodos::query()->where('nivel_id', $this->nivel_id)->where('ciclo_escolar_id', (int) $this->ciclo_escolar_id)
                ->with($this->slug_nivel === 'bachillerato' ? 'parcialBachillerato' : 'periodoBasica')->get()
                ->map(fn ($p) => [
                    'id' => (int) ($this->slug_nivel === 'bachillerato' ? $p->parcial_bachillerato_id : $p->periodo_basica_id),
                    'nombre' => $this->slug_nivel === 'bachillerato' ? ($p->parcialBachillerato?->descripcion ?? 'Parcial') : ($p->periodoBasica?->descripcion ?? 'Periodo'),
                ])->filter(fn ($v) => $v['id'] > 0)->unique('id')->values()->all()
            : [];
        $alumnos = []; $actuales = []; $captura = null;
        if ($this->asignacion_id && $this->evaluacion_id && $asignaciones->contains('id', (int) $this->asignacion_id)) {
            try {
                [$a, $p, $alumnos, $actuales] = $this->contexto((int) $this->asignacion_id);
                $captura = CalificacionDocenteCaptura::query()->where('asignacion_materia_id', $a->id)->where('periodo_id', $p->id)->first();
            } catch (ValidationException $exception) { /* Mostrar vacío mientras se seleccionan filtros. */ }
        }
        $avance = [];
        if ($this->evaluacion_id) {
            foreach ($asignaciones as $a) {
                try {
                    $p = $this->periodo($a);
                    $estado = CalificacionDocenteCaptura::query()->where('asignacion_materia_id', $a->id)->where('periodo_id', $p->id)->value('estado');
                    $avance[$a->id] = $estado ?? 'pendiente';
                } catch (ValidationException $e) { $avance[$a->id] = 'sin_periodo'; }
            }
        }
        return view('livewire.academico.calificaciones-por-docente', compact('ciclos', 'profesores', 'asignaciones', 'evaluaciones', 'alumnos', 'actuales', 'captura', 'avance'))
            ->layout('components.layouts.app', ['title' => 'Calificaciones por Docente PRO · '.ucfirst($this->slug_nivel)]);
    }
}
