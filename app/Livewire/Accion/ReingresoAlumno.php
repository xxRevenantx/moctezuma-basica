<?php

namespace App\Livewire\Accion;

use App\Models\AsignacionMateria;
use App\Models\Ciclo;
use App\Models\CicloEscolar;
use App\Models\DocumentoAlumno;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Periodos;
use App\Models\Semestre;
use App\Models\TipoDocumento;
use App\Services\AsignacionEscolarService;
use App\Services\CierreNivelReingresoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class ReingresoAlumno extends Component
{
    use WithFileUploads;

    public ?string $slug_nivel = null;
    public string $search = '';
    public ?int $alumno_id = null;
    public string $tipo_retorno = 'reingreso';
    public ?int $ciclo_escolar_id = null;
    public ?int $ciclo_id = null;
    public ?int $nivel_destino_id = null;
    public ?int $grado_destino_id = null;
    public ?int $generacion_destino_id = null;
    public ?int $generacion_sugerida_id = null;
    public ?string $generacion_sugerida_label = null;
    public bool $generacion_excepcional = false;
    public ?int $semestre_destino_id = null;
    public ?int $grupo_destino_id = null;
    public string $fecha_ingreso = '';
    public string $matricula = '';
    public string $escuela_procedencia = '';
    public string $cct_procedencia = '';
    public string $ciclo_procedencia = '';
    public string $ultimo_grado_procedencia = '';
    public string $observaciones_procedencia = '';
    public string $justificacion = '';
    public bool $documentacion_pendiente = true;
    public string $usuario_acceso = '';
    public bool $confirmar = false;
    public $constancia_traslado_pdf;
    public ?int $documento_respaldo_id = null;
    public ?int $alumno_reingresado_id = null;

    public ?int $periodo_externo_id = null;
    public ?int $asignacion_externa_id = null;
    public string $calificacion_externa = '';
    public string $observacion_calificacion = '';

    public Collection $niveles;
    public Collection $ciclosEscolares;
    public Collection $ciclos;

    public function mount(?string $slug_nivel = null): void
    {
        abort_unless(auth()->user()?->is_admin || auth()->user()?->canAccess('alumnos.editar'), 403);

        $this->slug_nivel = $slug_nivel;
        $this->niveles = Nivel::query()->orderBy('id')->get(['id', 'nombre', 'slug']);
        $this->ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual']);
        $this->ciclos = Ciclo::query()->orderBy('id')->get(['id', 'ciclo']);

        $actual = $this->ciclosEscolares->firstWhere('es_actual', true) ?: $this->ciclosEscolares->first();
        $this->ciclo_escolar_id = $actual?->id;
        $this->ciclo_id = $this->ciclos->first()?->id;
        $this->nivel_destino_id = $this->niveles->firstWhere('slug', $slug_nivel)?->id;
        $this->fecha_ingreso = now()->toDateString();

        $alumnoPreseleccionado = request()->integer('reingreso');
        if ($alumnoPreseleccionado > 0) {
            $this->seleccionarAlumno($alumnoPreseleccionado);
        }
    }

    public function updatedCicloEscolarId(): void
    {
        $this->generacion_destino_id = null;
        $this->generacion_sugerida_id = null;
        $this->generacion_sugerida_label = null;
        $this->generacion_excepcional = false;
        $this->grupo_destino_id = null;
        $this->proponerGeneracionDestino();
    }

    public function updatedNivelDestinoId(): void
    {
        $this->grado_destino_id = null;
        $this->generacion_destino_id = null;
        $this->generacion_sugerida_id = null;
        $this->generacion_sugerida_label = null;
        $this->generacion_excepcional = false;
        $this->semestre_destino_id = null;
        $this->grupo_destino_id = null;
    }

    public function updatedGradoDestinoId(): void
    {
        $this->semestre_destino_id = null;
        $this->generacion_destino_id = null;
        $this->generacion_sugerida_id = null;
        $this->generacion_sugerida_label = null;
        $this->generacion_excepcional = false;
        $this->grupo_destino_id = null;

        if (! $this->esBachillerato) {
            $this->proponerGeneracionDestino();
        }
    }

    public function updatedGeneracionDestinoId(): void
    {
        $this->generacion_excepcional = (bool) $this->generacion_destino_id
            && (! $this->generacion_sugerida_id
                || (int) $this->generacion_destino_id !== (int) $this->generacion_sugerida_id);
        $this->grupo_destino_id = null;
    }

    public function updatedSemestreDestinoId(): void
    {
        $semestre = $this->semestre_destino_id
            ? Semestre::query()->find($this->semestre_destino_id)
            : null;

        if ($semestre?->grado_id) {
            $this->grado_destino_id = (int) $semestre->grado_id;
        }

        $this->generacion_destino_id = null;
        $this->generacion_sugerida_id = null;
        $this->generacion_sugerida_label = null;
        $this->generacion_excepcional = false;
        $this->grupo_destino_id = null;
        $this->proponerGeneracionDestino();
    }

    public function seleccionarAlumno(int $id): void
    {
        $this->alumno_id = $id;
        $alumno = $this->alumnoSeleccionado;

        if ($alumno) {
            $estatus = $this->normalizarEstatus((string) $alumno->estatus);
            $this->tipo_retorno = $estatus === 'egresado' ? 'reingreso' : 'reincorporacion';
            $this->nivel_destino_id = $this->tipo_retorno === 'reincorporacion'
                ? $alumno->nivel_id
                : ($this->nivel_destino_id ?: $alumno->nivel_id);

            $procedencia = data_get($alumno->ultimoMovimiento?->estado_nuevo, 'procedencia', []);
            $this->escuela_procedencia = (string) ($procedencia['escuela_procedencia'] ?? '');
            $this->cct_procedencia = (string) ($procedencia['cct_procedencia'] ?? '');
            $this->ciclo_procedencia = (string) ($procedencia['ciclo_procedencia'] ?? '');
            $this->ultimo_grado_procedencia = (string) ($procedencia['ultimo_grado_procedencia'] ?? '');
        }

        $this->resetValidation();
    }

    public function limpiarAlumno(): void
    {
        $this->alumno_id = null;
        $this->alumno_reingresado_id = null;
        $this->documento_respaldo_id = null;
        $this->constancia_traslado_pdf = null;
        $this->confirmar = false;
    }

    public function getResultadosProperty(): Collection
    {
        $termino = trim($this->search);
        if (mb_strlen($termino) < 2) {
            return collect();
        }

        return Inscripcion::withTrashed()
            ->with([
                'nivel:id,nombre,slug',
                'grado:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
                'ultimoMovimiento',
            ])
            ->where(function (Builder $query) use ($termino): void {
                $like = "%{$termino}%";
                $query->where('matricula', 'like', $like)
                    ->orWhere('curp', 'like', $like)
                    ->orWhereRaw("CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?", [$like]);
            })
            ->where(function (Builder $query): void {
                $query->where('activo', false)
                    ->orWhereIn('estatus', [
                        'egresado',
                        'traslado',
                        'trasladado',
                        'baja_temporal',
                        'baja_definitiva',
                        'inactivo',
                        'suspendido',
                    ]);
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->limit(15)
            ->get();
    }

    public function getAlumnoSeleccionadoProperty(): ?Inscripcion
    {
        return $this->alumno_id
            ? Inscripcion::withTrashed()->with([
                'nivel:id,nombre,slug',
                'grado:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
                'grupo.asignacionGrupo:id,nombre',
                'semestre:id,numero',
                'documentosActuales.tipoDocumento:id,nombre,slug',
                'ultimoMovimiento',
            ])->find($this->alumno_id)
            : null;
    }

    public function getUltimaTrayectoriaSeleccionadaProperty(): ?Inscripcion
    {
        return $this->alumnoSeleccionado;
    }

    public function getGeneracionDestinoSeleccionadaProperty(): ?Generacion
    {
        return $this->generaciones->firstWhere('id', $this->generacion_destino_id);
    }

    public function ultimaTrayectoriaDe(?Inscripcion $alumno): ?Inscripcion
    {
        return $alumno;
    }

    public function textoGeneracion(?Generacion $generacion): string
    {
        return $generacion
            ? $generacion->anio_ingreso.'-'.$generacion->anio_egreso
            : 'Sin generación';
    }

    private function proponerGeneracionDestino(): void
    {
        if (! $this->ciclo_escolar_id || ! $this->nivel_destino_id || ! $this->grado_destino_id) {
            return;
        }

        $ciclo = CicloEscolar::query()->find($this->ciclo_escolar_id);
        $nivel = Nivel::query()->find($this->nivel_destino_id);
        $grado = Grado::query()->find($this->grado_destino_id);
        $semestre = $this->semestre_destino_id
            ? Semestre::query()->find($this->semestre_destino_id)
            : null;

        if (! $ciclo || ! $nivel || ! $grado || ($nivel->slug === 'bachillerato' && ! $semestre)) {
            return;
        }

        $servicio = app(AsignacionEscolarService::class);
        $this->generacion_sugerida_label = $servicio->etiquetaGeneracionEsperada(
            $ciclo,
            $nivel,
            $grado,
            $semestre,
        );

        $generacion = $servicio->resolverGeneracion(
            $ciclo,
            $nivel,
            $grado,
            $semestre,
        );

        $this->generacion_sugerida_id = $generacion?->id ? (int) $generacion->id : null;

        if ($this->generacion_sugerida_id) {
            $this->generacion_destino_id = $this->generacion_sugerida_id;
            $this->generacion_excepcional = false;
        }
    }

    public function getGradosProperty(): Collection
    {
        return $this->nivel_destino_id
            ? Grado::query()->where('nivel_id', $this->nivel_destino_id)->orderBy('orden')->get(['id', 'nivel_id', 'nombre', 'orden'])
            : collect();
    }

    public function getGeneracionesProperty(): Collection
    {
        if (! $this->nivel_destino_id || ! $this->ciclo_escolar_id || ! $this->grado_destino_id) {
            return collect();
        }

        return Generacion::query()
            ->where('nivel_id', $this->nivel_destino_id)
            ->where('status', true)
            ->when(
                $this->generacion_sugerida_id,
                fn (Builder $query) => $query->orderByRaw('id = ? desc', [$this->generacion_sugerida_id])
            )
            ->orderByDesc('anio_ingreso')
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso']);
    }

    public function getSemestresProperty(): Collection
    {
        return $this->esBachillerato && $this->grado_destino_id
            ? Semestre::query()->where('grado_id', $this->grado_destino_id)->orderBy('numero')->get(['id', 'grado_id', 'numero'])
            : collect();
    }

    public function getGruposProperty(): Collection
    {
        if (! $this->nivel_destino_id || ! $this->grado_destino_id || ! $this->generacion_destino_id) {
            return collect();
        }

        return Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->withCount([
                'inscripciones as alumnos_activos_count' => fn (Builder $query) => $query
                    ->where('activo', true)
                    ->whereNull('deleted_at'),
            ])
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('estado', 'activo')
            ->where('nivel_id', $this->nivel_destino_id)
            ->where('grado_id', $this->grado_destino_id)
            ->where('generacion_id', $this->generacion_destino_id)
            ->when($this->esBachillerato, fn (Builder $query) => $query->where('semestre_id', $this->semestre_destino_id))
            ->when(! $this->esBachillerato, fn (Builder $query) => $query->whereNull('semestre_id'))
            ->orderBy('asignacion_grupo_id')
            ->get();
    }

    public function getEsBachilleratoProperty(): bool
    {
        $nivel = $this->niveles->firstWhere('id', $this->nivel_destino_id);

        return str_contains(
            mb_strtolower(($nivel?->slug ?? '').' '.($nivel?->nombre ?? '')),
            'bachillerato'
        );
    }

    public function getAsignacionesExternasProperty(): Collection
    {
        $alumno = $this->alumnoReingresado();
        if (! $alumno || ! $this->ciclo_escolar_id) {
            return collect();
        }

        return AsignacionMateria::query()
            ->with('materia:id,nombre')
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $alumno->nivel_id)
            ->where('grado_id', $alumno->grado_id)
            ->where('generacion_id', $alumno->generacion_id)
            ->where('grupo_id', $alumno->grupo_id)
            ->when($alumno->semestre_id, fn (Builder $query) => $query->where('semestre_id', $alumno->semestre_id), fn (Builder $query) => $query->whereNull('semestre_id'))
            ->where('estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA)
            ->orderBy('orden')
            ->get();
    }

    public function getPeriodosExternosProperty(): Collection
    {
        $alumno = $this->alumnoReingresado();
        if (! $alumno || ! $this->ciclo_escolar_id) {
            return collect();
        }

        return Periodos::query()
            ->with(['periodoBasica:id,periodo', 'parcialBachillerato:id,parcial', 'cicloEscolar:id,inicio_anio,fin_anio'])
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $alumno->nivel_id)
            ->where('generacion_id', $alumno->generacion_id)
            ->when($alumno->semestre_id, fn (Builder $query) => $query->where('semestre_id', $alumno->semestre_id), fn (Builder $query) => $query->whereNull('semestre_id'))
            ->orderBy('fecha_inicio')
            ->get();
    }

    public function confirmarReingreso(): void
    {
        abort_unless(auth()->user()?->is_admin || auth()->user()?->canAccess('alumnos.editar'), 403);

        $this->validate([
            'alumno_id' => ['required', 'exists:inscripciones,id'],
            'tipo_retorno' => ['required', 'in:reingreso,reincorporacion'],
            'ciclo_escolar_id' => ['required', 'exists:ciclo_escolares,id'],
            'ciclo_id' => ['required', 'exists:ciclos,id'],
            'nivel_destino_id' => ['required', 'exists:niveles,id'],
            'grado_destino_id' => ['required', 'exists:grados,id'],
            'generacion_destino_id' => ['required', 'exists:generaciones,id'],
            'semestre_destino_id' => [$this->esBachillerato ? 'required' : 'nullable', 'exists:semestres,id'],
            'grupo_destino_id' => ['required', 'exists:grupos,id'],
            'justificacion' => [$this->generacion_excepcional ? 'required' : 'nullable', 'string', 'min:10', 'max:1000'],
            'fecha_ingreso' => ['required', 'date'],
            'matricula' => ['nullable', 'string', 'max:50'],
            'constancia_traslado_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        if (! $this->confirmar) {
            $this->addError('confirmar', 'Confirma el resumen antes de continuar.');
            return;
        }

        $alumno = Inscripcion::withTrashed()->findOrFail($this->alumno_id);

        try {
            $alumnoActualizado = app(CierreNivelReingresoService::class)->reingresarExalumno(
                $alumno,
                $this->tipo_retorno,
                [
                    'ciclo_escolar_id' => $this->ciclo_escolar_id,
                    'ciclo_id' => $this->ciclo_id,
                    'nivel_id' => $this->nivel_destino_id,
                    'grado_id' => $this->grado_destino_id,
                    'generacion_id' => $this->generacion_destino_id,
                    'semestre_id' => $this->esBachillerato ? $this->semestre_destino_id : null,
                    'grupo_id' => $this->grupo_destino_id,
                    'fecha_ingreso' => $this->fecha_ingreso,
                    'matricula' => trim($this->matricula) ?: null,
                    'justificacion' => trim($this->justificacion) ?: null,
                    'usuario_acceso_activo' => $this->usuario_acceso === '' ? null : $this->usuario_acceso === '1',
                ],
                [
                    'escuela_procedencia' => trim($this->escuela_procedencia) ?: null,
                    'cct_procedencia' => trim($this->cct_procedencia) ?: null,
                    'ciclo_procedencia' => trim($this->ciclo_procedencia) ?: null,
                    'ultimo_grado_procedencia' => trim($this->ultimo_grado_procedencia) ?: null,
                    'observaciones_procedencia' => trim($this->observaciones_procedencia) ?: null,
                    'documentacion_pendiente' => $this->documentacion_pendiente && ! $this->constancia_traslado_pdf,
                ],
                auth()->id()
            );

            if ($this->constancia_traslado_pdf) {
                $this->documento_respaldo_id = $this->guardarConstanciaTraslado($alumnoActualizado);
                $documento = DocumentoAlumno::query()->findOrFail($this->documento_respaldo_id);

                app(\App\Services\ConstanciaTrasladoService::class)->registrarExterna(
                    $alumnoActualizado,
                    $documento,
                    'Documento externo registrado durante el retorno del alumno.',
                    auth()->id()
                );

                $alumnoActualizado->update(['documentacion_reingreso_pendiente' => false]);
            }

            $this->alumno_reingresado_id = $alumnoActualizado->id;
            $this->confirmar = false;
            $this->dispatch('swal', [
                'title' => $this->tipo_retorno === 'reingreso'
                    ? 'Exalumno reingresado correctamente.'
                    : 'Alumno reincorporado correctamente.',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $campo => $mensajes) {
                $this->addError($campo, $mensajes[0]);
            }
        }
    }

    public function guardarCalificacionExterna(): void
    {
        abort_unless(auth()->user()?->is_admin || auth()->user()?->canAccess('calificaciones.capturar'), 403);

        $this->validate([
            'alumno_reingresado_id' => ['required', 'exists:inscripciones,id'],
            'periodo_externo_id' => ['required', 'exists:periodos,id'],
            'asignacion_externa_id' => ['required', 'exists:asignacion_materias,id'],
            'calificacion_externa' => ['required', 'string', 'max:5'],
        ]);

        app(CierreNivelReingresoService::class)->capturarCalificacionExterna(
            Inscripcion::findOrFail($this->alumno_reingresado_id),
            $this->asignacion_externa_id,
            $this->periodo_externo_id,
            $this->calificacion_externa,
            $this->documento_respaldo_id,
            $this->escuela_procedencia,
            $this->observacion_calificacion,
            auth()->id()
        );

        $this->calificacion_externa = '';
        $this->observacion_calificacion = '';
        $this->dispatch('swal', [
            'title' => 'Calificación externa integrada y marcada como equivalencia.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function nombreAlumno(?Inscripcion $alumno): string
    {
        return trim(($alumno?->apellido_paterno ?? '').' '.($alumno?->apellido_materno ?? '').' '.($alumno?->nombre ?? ''));
    }

    public function textoGrupo($grupo): string
    {
        $nombre = $grupo?->asignacionGrupo?->nombre ?? $grupo?->grupo ?? $grupo?->nombre ?? '—';
        $alumnos = (int) ($grupo?->alumnos_activos_count ?? 0);

        return "{$nombre} · {$alumnos} alumnos · cupo ilimitado";
    }

    public function etiquetaPeriodo($periodo): string
    {
        $nombre = $periodo->periodoBasica?->periodo
            ?: $periodo->parcialBachillerato?->parcial
            ?: 'Periodo '.$periodo->id;

        return $nombre.($periodo->cicloEscolar ? ' · '.$periodo->cicloEscolar->nombre : '');
    }

    private function guardarConstanciaTraslado(Inscripcion $alumno): int
    {
        $tipo = TipoDocumento::query()->where('slug', 'constancia-traslado-calificaciones')->firstOrFail();
        $version = ((int) DocumentoAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('tipo_documento_id', $tipo->id)
            ->where('nivel_id', $alumno->nivel_id)
            ->max('version')) + 1;

        DocumentoAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('tipo_documento_id', $tipo->id)
            ->where('nivel_id', $alumno->nivel_id)
            ->where('es_actual', true)
            ->update(['es_actual' => false, 'estado' => 'reemplazado']);

        $archivo = $this->constancia_traslado_pdf;
        $nombre = Str::uuid().'.pdf';
        $discoExpedientes = (string) config('filesystems.expedientes_disk', 'local');
        $hashSha256 = hash_file('sha256', $archivo->getRealPath()) ?: null;
        $ruta = $archivo->storeAs(
            "expedientes/{$alumno->id}/constancia-traslado/nivel-{$alumno->nivel_id}",
            $nombre,
            $discoExpedientes
        );

        return DocumentoAlumno::query()->create([
            'inscripcion_id' => $alumno->id,
            'tipo_documento_id' => $tipo->id,
            'nivel_id' => $alumno->nivel_id,
            'grado_id' => $alumno->grado_id,
            'grupo_id' => $alumno->grupo_id,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'fecha_documento' => now()->toDateString(),
            'origen' => 'externo',
            'tipo_movimiento' => $this->tipo_retorno,
            'disco' => $discoExpedientes,
            'ruta' => $ruta,
            'nombre_original' => $archivo->getClientOriginalName(),
            'mime_type' => $archivo->getMimeType() ?: 'application/pdf',
            'tamano_bytes' => $archivo->getSize(),
            'hash_sha256' => $hashSha256,
            'version' => $version,
            'es_actual' => true,
            'estado' => 'validado',
            'observaciones' => 'Documento de procedencia registrado durante el retorno del alumno.',
            'subido_por' => auth()->id(),
            'validado_por' => auth()->id(),
            'validado_at' => now(),
        ])->id;
    }

    private function alumnoReingresado(): ?Inscripcion
    {
        return $this->alumno_reingresado_id
            ? Inscripcion::query()->find($this->alumno_reingresado_id)
            : null;
    }

    private function normalizarEstatus(string $estatus): string
    {
        return match (mb_strtolower(trim($estatus))) {
            'trasladado' => 'traslado',
            default => mb_strtolower(trim($estatus)),
        };
    }

    public function render()
    {
        return view('livewire.accion.reingreso-alumno');
    }
}
