<?php

namespace App\Livewire\Documentacion;

use App\Models\CicloEscolar;
use App\Models\DocumentoAlumno;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\MovimientoAlumno;
use App\Models\TipoDocumento;
use App\Services\ExpedienteDigitalService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

class ExpedientesDigitales extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $buscar = '';
    public ?int $nivel_id = null;
    public string $estado_expediente = 'todos';
    public int $perPage = 20;

    public ?int $alumnoSeleccionadoId = null;

    public bool $mostrarCarga = false;
    public bool $reemplazaDocumentoActual = false;
    public ?int $tipo_documento_id = null;
    public ?int $nivel_certificado_id = null;
    public ?int $grado_documento_id = null;
    public ?int $grupo_documento_id = null;
    public ?int $ciclo_escolar_documento_id = null;
    public ?string $fecha_documento = null;
    public string $folio_documento = '';
    public string $origen_documento = 'externo';
    public string $tipo_movimiento_documento = 'baja_definitiva';
    public string $motivo_documento = '';
    public string $observaciones = '';
    public $archivo = null;

    public array $tiposDocumentos = [];
    public array $niveles = [];
    public array $grados = [];
    public array $grupos = [];
    public array $ciclosEscolares = [];

    protected $paginationTheme = 'tailwind';

    public function mount(?int $inscripcionId = null): void
    {
        $this->autorizarAdmin();

        $servicio = app(ExpedienteDigitalService::class);

        $this->tiposDocumentos = $servicio->tiposActivos()
            ->map(fn(TipoDocumento $tipo) => [
                'id' => $tipo->id,
                'nombre' => $tipo->nombre,
                'slug' => $tipo->slug,
                'requiere_nivel' => $tipo->requiere_nivel,
            ])
            ->values()
            ->all();

        $this->niveles = $servicio->niveles()
            ->map(fn($nivel) => [
                'id' => $nivel->id,
                'nombre' => $nivel->nombre,
                'slug' => $nivel->slug,
                'color' => $nivel->color,
            ])
            ->values()
            ->all();

        $this->grados = Grado::query()
            ->select('id', 'nivel_id', 'nombre', 'orden')
            ->orderBy('nivel_id')
            ->orderBy('orden')
            ->get()
            ->map(fn(Grado $grado) => [
                'id' => $grado->id,
                'nivel_id' => $grado->nivel_id,
                'nombre' => $grado->nombre,
                'orden' => $grado->orden,
            ])->all();

        $this->grupos = Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->select('id', 'nivel_id', 'grado_id', 'asignacion_grupo_id')
            ->orderBy('nivel_id')
            ->orderBy('grado_id')
            ->get()
            ->map(fn(Grupo $grupo) => [
                'id' => $grupo->id,
                'nivel_id' => $grupo->nivel_id,
                'grado_id' => $grupo->grado_id,
                'nombre' => $grupo->asignacionGrupo?->nombre ?? 'Sin grupo',
            ])->all();

        $this->ciclosEscolares = CicloEscolar::query()
            ->select('id', 'inicio_anio', 'fin_anio')
            ->orderByDesc('inicio_anio')
            ->get()
            ->map(fn(CicloEscolar $ciclo) => [
                'id' => $ciclo->id,
                'nombre' => $ciclo->inicio_anio . '-' . $ciclo->fin_anio,
            ])->all();

        $this->fecha_documento = now()->format('Y-m-d');

        if ($inscripcionId && Inscripcion::withTrashed()->whereKey($inscripcionId)->exists()) {
            $this->alumnoSeleccionadoId = $inscripcionId;
        }
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedNivelId($value): void
    {
        $this->nivel_id = $value ? (int) $value : null;
        $this->resetPage();
    }

    public function updatedEstadoExpediente(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->buscar = '';
        $this->nivel_id = null;
        $this->estado_expediente = 'todos';
        $this->perPage = 20;
        $this->resetPage();
    }

    public function verExpediente(int $alumnoId): void
    {
        $this->autorizarAdmin();
        abort_unless(Inscripcion::withTrashed()->whereKey($alumnoId)->exists(), 404);

        $this->alumnoSeleccionadoId = $alumnoId;
        $this->cerrarCarga();
    }

    public function cerrarExpediente(): void
    {
        $this->alumnoSeleccionadoId = null;
        $this->cerrarCarga();
    }

    public function abrirCarga(int $tipoId, ?int $nivelId = null, ?int $gradoId = null, ?int $cicloId = null): void
    {
        $this->autorizarAdmin();
        abort_unless($this->alumnoSeleccionadoId, 422, 'Selecciona un alumno.');
        $this->asegurarAlumnoModificable();

        $tipo = TipoDocumento::query()
            ->where('activo', true)
            ->findOrFail($tipoId);

        $this->resetValidation();
        $this->archivo = null;
        $this->tipo_documento_id = $tipo->id;
        $this->nivel_certificado_id = $nivelId;
        $this->grado_documento_id = $gradoId;
        $this->grupo_documento_id = null;
        $this->ciclo_escolar_documento_id = $cicloId;
        $this->fecha_documento = now()->format('Y-m-d');
        $this->folio_documento = '';
        $this->origen_documento = 'externo';
        $this->tipo_movimiento_documento = 'baja_definitiva';
        $this->motivo_documento = '';
        $this->observaciones = '';

        if (!$tipo->requiere_nivel) {
            $this->nivel_certificado_id = null;
            $this->grado_documento_id = null;
            $this->ciclo_escolar_documento_id = null;
        }

        $this->actualizarIndicadorReemplazo();
        $this->mostrarCarga = true;
    }

    public function cerrarCarga(): void
    {
        $this->mostrarCarga = false;
        $this->reemplazaDocumentoActual = false;
        $this->archivo = null;
        $this->tipo_documento_id = null;
        $this->nivel_certificado_id = null;
        $this->grado_documento_id = null;
        $this->grupo_documento_id = null;
        $this->ciclo_escolar_documento_id = null;
        $this->fecha_documento = now()->format('Y-m-d');
        $this->folio_documento = '';
        $this->origen_documento = 'externo';
        $this->tipo_movimiento_documento = 'baja_definitiva';
        $this->motivo_documento = '';
        $this->observaciones = '';
        $this->resetValidation();
    }

    public function updatedTipoDocumentoId($value): void
    {
        $this->tipo_documento_id = $value ? (int) $value : null;

        $tipo = collect($this->tiposDocumentos)->firstWhere('id', $this->tipo_documento_id);

        if (!($tipo['requiere_nivel'] ?? false)) {
            $this->nivel_certificado_id = null;
            $this->grado_documento_id = null;
            $this->grupo_documento_id = null;
            $this->ciclo_escolar_documento_id = null;
        }

        if (($tipo['slug'] ?? '') === 'boleta-final-grado') {
            $this->origen_documento = 'externo';
        }

        $this->actualizarIndicadorReemplazo();
    }

    public function updatedNivelCertificadoId($value): void
    {
        $this->nivel_certificado_id = $value ? (int) $value : null;
        $this->grado_documento_id = null;
        $this->grupo_documento_id = null;
        $this->actualizarIndicadorReemplazo();
    }

    public function updatedGradoDocumentoId($value): void
    {
        $this->grado_documento_id = $value ? (int) $value : null;
        $this->grupo_documento_id = null;
        $this->actualizarIndicadorReemplazo();
    }

    public function updatedCicloEscolarDocumentoId($value): void
    {
        $this->ciclo_escolar_documento_id = $value ? (int) $value : null;
        $this->actualizarIndicadorReemplazo();
    }

    public function subirDocumento(): void
    {
        $this->autorizarAdmin();
        abort_unless($this->alumnoSeleccionadoId, 422, 'Selecciona un alumno.');
        $this->asegurarAlumnoModificable();

        $this->validate([
            'tipo_documento_id' => ['required', 'integer', 'exists:tipos_documentos,id'],
        ], [
            'tipo_documento_id.required' => 'Selecciona el tipo de documento.',
            'tipo_documento_id.exists' => 'El tipo de documento seleccionado no es válido.',
        ]);

        $tipo = TipoDocumento::query()
            ->where('activo', true)
            ->findOrFail($this->tipo_documento_id);

        $esAcademico = in_array($tipo->slug, [
            'boleta-final-grado',
            'constancia-estudios',
            'constancia-baja-traslado',
        ], true);

        $reglas = [
            'tipo_documento_id' => ['required', 'integer', 'exists:tipos_documentos,id'],
            'archivo' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf,application/x-pdf', 'max:5120'],
            'fecha_documento' => ['nullable', 'date'],
            'folio_documento' => ['nullable', 'string', 'max:100'],
            'origen_documento' => ['required', 'in:externo,subido,digitalizado'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];

        if ($tipo->requiere_nivel) {
            $reglas['nivel_certificado_id'] = ['required', 'integer', 'exists:niveles,id'];
        }

        if ($esAcademico) {
            $reglas['grado_documento_id'] = ['required', 'integer', 'exists:grados,id'];
            $reglas['grupo_documento_id'] = ['nullable', 'integer', 'exists:grupos,id'];
            $reglas['ciclo_escolar_documento_id'] = ['required', 'integer', 'exists:ciclo_escolares,id'];
        }

        if ($tipo->slug === 'constancia-baja-traslado') {
            $reglas['tipo_movimiento_documento'] = ['required', 'in:baja_definitiva,baja_temporal,traslado,cambio_interno_nivel,cambio_grupo,reingreso'];
            $reglas['motivo_documento'] = ['required', 'string', 'max:2000'];
        }

        $this->validate($reglas, [
            'archivo.required' => 'Selecciona un archivo PDF.',
            'archivo.mimes' => 'El documento debe ser un archivo PDF.',
            'archivo.mimetypes' => 'El archivo seleccionado no es un PDF válido.',
            'archivo.max' => 'El PDF no debe superar los 5 MB.',
            'nivel_certificado_id.required' => 'Selecciona el nivel relacionado con el documento.',
            'grado_documento_id.required' => 'Selecciona el grado relacionado con el documento.',
            'ciclo_escolar_documento_id.required' => 'Selecciona el ciclo escolar del documento.',
            'motivo_documento.required' => 'Escribe el motivo de la baja, traslado o movimiento.',
        ]);

        $nivelId = $tipo->requiere_nivel ? $this->nivel_certificado_id : null;
        $gradoId = $esAcademico ? $this->grado_documento_id : null;
        $grupoId = $esAcademico ? $this->grupo_documento_id : null;
        $cicloEscolarId = $esAcademico ? $this->ciclo_escolar_documento_id : null;

        if ($esAcademico) {
            $gradoValido = Grado::query()
                ->whereKey($gradoId)
                ->where('nivel_id', $nivelId)
                ->exists();

            if (!$gradoValido) {
                $this->addError('grado_documento_id', 'El grado no pertenece al nivel seleccionado.');
                return;
            }

            if ($grupoId) {
                $grupoValido = Grupo::query()
                    ->whereKey($grupoId)
                    ->where('nivel_id', $nivelId)
                    ->where('grado_id', $gradoId)
                    ->exists();

                if (!$grupoValido) {
                    $this->addError('grupo_documento_id', 'El grupo no pertenece al nivel y grado seleccionados.');
                    return;
                }
            }
        }

        if ($tipo->slug === 'boleta-final-grado') {
            $nivel = collect($this->niveles)->firstWhere('id', $nivelId);
            $textoNivel = Str::lower(trim(($nivel['slug'] ?? '') . ' ' . ($nivel['nombre'] ?? '')));

            if (!Str::contains($textoNivel, ['primaria', 'secundaria'])) {
                $this->addError('nivel_certificado_id', 'Las boletas finales solo se manejan para primaria y secundaria.');
                return;
            }
        }

        $rutaGuardada = null;
        $nombreOriginal = Str::limit($this->archivo->getClientOriginalName(), 250, '');
        $tamanoBytes = (int) $this->archivo->getSize();
        $hashSha256 = hash_file('sha256', $this->archivo->getRealPath()) ?: null;
        $reemplazaAnterior = !in_array($tipo->slug, ['constancia-estudios', 'constancia-baja-traslado'], true);

        try {
            DB::transaction(function () use ($tipo, $nivelId, $gradoId, $grupoId, $cicloEscolarId, $nombreOriginal, $tamanoBytes, $hashSha256, $reemplazaAnterior, &$rutaGuardada) {
                $consultaVersiones = DocumentoAlumno::query()
                    ->where('inscripcion_id', $this->alumnoSeleccionadoId)
                    ->where('tipo_documento_id', $tipo->id)
                    ->when($nivelId, fn(Builder $query) => $query->where('nivel_id', $nivelId), fn(Builder $query) => $query->whereNull('nivel_id'))
                    ->when($gradoId, fn(Builder $query) => $query->where('grado_id', $gradoId), fn(Builder $query) => $query->whereNull('grado_id'))
                    ->when($cicloEscolarId, fn(Builder $query) => $query->where('ciclo_escolar_id', $cicloEscolarId), fn(Builder $query) => $query->whereNull('ciclo_escolar_id'))
                    ->lockForUpdate();

                $version = ((int) (clone $consultaVersiones)->max('version')) + 1;

                if ($reemplazaAnterior) {
                    (clone $consultaVersiones)
                        ->where('es_actual', true)
                        ->update([
                            'es_actual' => false,
                            'estado' => 'reemplazado',
                            'updated_at' => now(),
                        ]);
                }

                $segmentos = [
                    'expedientes',
                    $this->alumnoSeleccionadoId,
                    $tipo->slug,
                    $nivelId ? 'nivel-' . $nivelId : 'general',
                ];

                if ($gradoId) {
                    $segmentos[] = 'grado-' . $gradoId;
                }

                if ($cicloEscolarId) {
                    $segmentos[] = 'ciclo-' . $cicloEscolarId;
                }

                $directorio = implode('/', $segmentos);
                $nombreInterno = Str::uuid() . '.pdf';
                $rutaGuardada = $this->archivo->storeAs($directorio, $nombreInterno, 'local');

                if (!$rutaGuardada) {
                    throw new \RuntimeException('No fue posible guardar el archivo.');
                }

                $documento = DocumentoAlumno::query()->create([
                    'inscripcion_id' => $this->alumnoSeleccionadoId,
                    'tipo_documento_id' => $tipo->id,
                    'nivel_id' => $nivelId,
                    'grado_id' => $gradoId,
                    'grupo_id' => $grupoId,
                    'ciclo_escolar_id' => $cicloEscolarId,
                    'fecha_documento' => $this->fecha_documento ?: now()->toDateString(),
                    'folio' => trim($this->folio_documento) ?: null,
                    'origen' => $this->origen_documento,
                    'tipo_movimiento' => $tipo->slug === 'constancia-baja-traslado' ? $this->tipo_movimiento_documento : null,
                    'motivo' => $tipo->slug === 'constancia-baja-traslado' ? trim($this->motivo_documento) : null,
                    'disco' => 'local',
                    'ruta' => $rutaGuardada,
                    'nombre_original' => $nombreOriginal,
                    'mime_type' => 'application/pdf',
                    'tamano_bytes' => $tamanoBytes,
                    'hash_sha256' => $hashSha256,
                    'version' => $version,
                    'es_actual' => true,
                    'estado' => 'recibido',
                    'observaciones' => trim($this->observaciones) ?: null,
                    'subido_por' => auth()->id(),
                ]);

                if ($tipo->slug === 'constancia-baja-traslado') {
                    MovimientoAlumno::query()->create([
                        'inscripcion_id' => $this->alumnoSeleccionadoId,
                            'documento_alumno_id' => $documento->id,
                        'tipo' => $this->tipo_movimiento_documento,
                        'fecha' => $this->fecha_documento ?: now()->toDateString(),
                        'motivo' => trim($this->motivo_documento),
                        'observaciones' => trim($this->observaciones) ?: 'Documento externo registrado sin modificar el estado de la inscripción.',
                        'registrado_por' => auth()->id(),
                    ]);
                }
            });

            $tipoGuardadoId = $tipo->id;
            $nivelGuardadoId = $nivelId;
            $gradoGuardadoId = $gradoId;
            $cicloGuardadoId = $cicloEscolarId;

            $this->cerrarCarga();

            $mensaje = $reemplazaAnterior
                ? 'Documento guardado. La versión anterior se conservó en el historial.'
                : 'Documento académico agregado al historial del alumno.';

            $this->dispatch(
                'documento-guardado',
                tipoId: $tipoGuardadoId,
                nivelId: $nivelGuardadoId,
                gradoId: $gradoGuardadoId,
                cicloId: $cicloGuardadoId,
            );
            $this->dispatch('notify', type: 'success', message: $mensaje);
        } catch (Throwable $e) {
            if ($rutaGuardada) {
                Storage::disk('local')->delete($rutaGuardada);
            }

            report($e);
            $this->addError('archivo', 'No fue posible guardar el documento. Inténtalo nuevamente.');
        }
    }

    public function actualizarEstado(int $documentoId, string $estado): void
    {
        $this->autorizarAdmin();
        $this->asegurarAlumnoModificable();

        abort_unless(in_array($estado, DocumentoAlumno::ESTADOS, true), 422, 'Estado no válido.');

        $documento = DocumentoAlumno::query()
            ->where('inscripcion_id', $this->alumnoSeleccionadoId)
            ->findOrFail($documentoId);

        abort_unless($documento->es_actual, 422, 'Las versiones históricas no pueden cambiarse.');

        $datos = [
            'estado' => $estado,
        ];

        if ($estado === 'validado') {
            $datos['validado_por'] = auth()->id();
            $datos['validado_at'] = now();
        } else {
            $datos['validado_por'] = null;
            $datos['validado_at'] = null;
        }

        if ($estado === 'reemplazado') {
            $datos['es_actual'] = false;
        }

        $documento->update($datos);

        $this->dispatch('notify', type: 'success', message: 'Estado del documento actualizado.');
    }

    public function nombreCompleto(Inscripcion $alumno): string
    {
        return trim(
            ($alumno->apellido_paterno ?? '') . ' ' .
            ($alumno->apellido_materno ?? '') . ' ' .
            ($alumno->nombre ?? '')
        );
    }

    private function consultaAlumnos(): Builder
    {
        return Inscripcion::withTrashed()
            ->select([
                'id',
                'matricula',
                'folio',
                'curp',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'nivel_id',
                'grado_id',
                'grupo_id',
                'generacion_id',
                'semestre_id',
                'tutor_id',
                'foto_path',
                'activo',
                'fecha_baja',
                'deleted_at',
            ])
            ->with([
                'nivel:id,nombre,slug,color',
                'grado:id,nombre,orden',
                'grupo:id,asignacion_grupo_id',
                'grupo.asignacionGrupo:id,nombre',
                'tutor:id,nombre,apellido_paterno,apellido_materno,parentesco',
                'documentos.tipoDocumento:id,nombre,slug,es_general,requiere_nivel,orden',
                'documentos.nivel:id,nombre,slug,color',
                'documentos.grado:id,nombre,orden',
                'documentos.grupo:id,asignacion_grupo_id',
                'documentos.grupo.asignacionGrupo:id,nombre',
                'documentos.cicloEscolar:id,inicio_anio,fin_anio',
                'documentos.usuarioQueSubio:id,name',
                'documentos.usuarioQueValido:id,name',
            ])
            ->when($this->nivel_id, fn(Builder $query) => $query->where('nivel_id', $this->nivel_id))
            ->when(trim($this->buscar) !== '', function (Builder $query) {
                $buscar = '%' . trim($this->buscar) . '%';

                $query->where(function (Builder $subconsulta) use ($buscar) {
                    $subconsulta->where('matricula', 'like', $buscar)
                        ->orWhere('folio', 'like', $buscar)
                        ->orWhere('curp', 'like', $buscar)
                        ->orWhere('nombre', 'like', $buscar)
                        ->orWhere('apellido_paterno', 'like', $buscar)
                        ->orWhere('apellido_materno', 'like', $buscar)
                        ->orWhereRaw("CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?", [$buscar])
                        ->orWhereRaw("CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?", [$buscar]);
                });
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre');
    }

    private function aplicarFiltroEstado(Collection $alumnos): Collection
    {
        return match ($this->estado_expediente) {
            'completos' => $alumnos->filter(fn(Inscripcion $alumno) => !$alumno->trashed() && $alumno->activo && $alumno->resumen_documental['completo']),
            'incompletos' => $alumnos->filter(fn(Inscripcion $alumno) => !$alumno->trashed() && $alumno->activo && !$alumno->resumen_documental['completo']),
            'bajas' => $alumnos->filter(fn(Inscripcion $alumno) => $alumno->trashed() || !$alumno->activo),
            default => $alumnos,
        };
    }

    private function paginar(Collection $items): LengthAwarePaginator
    {
        $paginaActual = LengthAwarePaginator::resolveCurrentPage('page');
        $total = $items->count();
        $ultimaPagina = max((int) ceil($total / max($this->perPage, 1)), 1);

        if ($paginaActual > $ultimaPagina) {
            $paginaActual = $ultimaPagina;
        }

        return new LengthAwarePaginator(
            $items->forPage($paginaActual, $this->perPage)->values(),
            $total,
            $this->perPage,
            $paginaActual,
            [
                'path' => request()->url(),
                'query' => request()->query(),
                'pageName' => 'page',
            ]
        );
    }

    private function actualizarIndicadorReemplazo(): void
    {
        $this->reemplazaDocumentoActual = false;

        if (!$this->alumnoSeleccionadoId || !$this->tipo_documento_id) {
            return;
        }

        $tipo = TipoDocumento::query()
            ->where('activo', true)
            ->find($this->tipo_documento_id);

        if (!$tipo || in_array($tipo->slug, ['constancia-estudios', 'constancia-baja-traslado'], true)) {
            return;
        }

        $esAcademico = $tipo->slug === 'boleta-final-grado';

        if ($tipo->requiere_nivel && !$this->nivel_certificado_id) {
            return;
        }

        if ($esAcademico && (!$this->grado_documento_id || !$this->ciclo_escolar_documento_id)) {
            return;
        }

        $nivelId = $tipo->requiere_nivel ? $this->nivel_certificado_id : null;
        $gradoId = $esAcademico ? $this->grado_documento_id : null;
        $cicloEscolarId = $esAcademico ? $this->ciclo_escolar_documento_id : null;

        $this->reemplazaDocumentoActual = DocumentoAlumno::query()
            ->where('inscripcion_id', $this->alumnoSeleccionadoId)
            ->where('tipo_documento_id', $tipo->id)
            ->where('es_actual', true)
            ->when(
                $nivelId,
                fn(Builder $query) => $query->where('nivel_id', $nivelId),
                fn(Builder $query) => $query->whereNull('nivel_id')
            )
            ->when(
                $gradoId,
                fn(Builder $query) => $query->where('grado_id', $gradoId),
                fn(Builder $query) => $query->whereNull('grado_id')
            )
            ->when(
                $cicloEscolarId,
                fn(Builder $query) => $query->where('ciclo_escolar_id', $cicloEscolarId),
                fn(Builder $query) => $query->whereNull('ciclo_escolar_id')
            )
            ->exists();
    }

    private function asegurarAlumnoModificable(): void
    {
        $alumno = Inscripcion::withTrashed()->find($this->alumnoSeleccionadoId);

        abort_unless($alumno, 404, 'El alumno no existe.');
        abort_if(
            $alumno->trashed() || !$alumno->activo,
            422,
            'El expediente de un alumno dado de baja es únicamente histórico.'
        );
    }

    private function autorizarAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403, 'No tienes permiso para administrar expedientes digitales.');
    }

    public function render()
    {
        $this->autorizarAdmin();

        $servicio = app(ExpedienteDigitalService::class);

        $todos = $this->consultaAlumnos()
            ->get()
            ->map(function (Inscripcion $alumno) use ($servicio) {
                $alumno->setAttribute('resumen_documental', $servicio->resumen($alumno));

                return $alumno;
            });

        $metricas = [
            'total' => $todos->count(),
            'completos' => $todos->filter(fn(Inscripcion $alumno) => !$alumno->trashed() && $alumno->activo && $alumno->resumen_documental['completo'])->count(),
            'incompletos' => $todos->filter(fn(Inscripcion $alumno) => !$alumno->trashed() && $alumno->activo && !$alumno->resumen_documental['completo'])->count(),
            'bajas' => $todos->filter(fn(Inscripcion $alumno) => $alumno->trashed() || !$alumno->activo)->count(),
        ];

        $alumnos = $this->paginar($this->aplicarFiltroEstado($todos)->values());

        $alumnoSeleccionado = null;
        $resumenSeleccionado = null;
        $documentosSeleccionados = collect();

        if ($this->alumnoSeleccionadoId) {
            $alumnoSeleccionado = Inscripcion::withTrashed()
                ->with([
                    'nivel:id,nombre,slug,color',
                    'grado:id,nombre,orden',
                    'grupo:id,asignacion_grupo_id',
                    'grupo.asignacionGrupo:id,nombre',
                    'generacion:id,nivel_id,anio_ingreso,anio_egreso,nombre,status',
                    'semestre:id,numero',
                    'tutor:id,nombre,apellido_paterno,apellido_materno,parentesco',
                    'cambiosAcademicos' => fn($query) => $query
                        ->with([
                            'generacion:id,nivel_id,anio_ingreso,anio_egreso,nombre,status',
                            'usuario:id,name',
                        ])
                        ->orderByDesc('realizado_at')
                        ->orderByDesc('id'),
                    'matriculasAlumno.nivel:id,nombre,slug',
                    'movimientos' => fn($query) => $query
                        ->with([
                            'usuario:id,name',
                            'cicloEscolar:id,inicio_anio,fin_anio',
                            'ciclo:id,ciclo',
                            'nivelAnterior:id,nombre,slug',
                            'nivelNuevo:id,nombre,slug',
                        ])
                        ->orderByDesc('fecha')
                        ->orderByDesc('id'),
                    'documentos.tipoDocumento:id,nombre,slug,es_general,requiere_nivel,orden',
                    'documentos.nivel:id,nombre,slug,color',
                    'documentos.grado:id,nombre,orden',
                    'documentos.grupo:id,asignacion_grupo_id',
                    'documentos.grupo.asignacionGrupo:id,nombre',
                    'documentos.cicloEscolar:id,inicio_anio,fin_anio',
                    'documentos.usuarioQueSubio:id,name',
                    'documentos.usuarioQueValido:id,name',
                ])
                ->find($this->alumnoSeleccionadoId);

            if ($alumnoSeleccionado) {
                $resumenSeleccionado = $servicio->resumen($alumnoSeleccionado);
                $documentosSeleccionados = $servicio->documentosOrdenados($alumnoSeleccionado);
            }
        }

        return view('livewire.documentacion.expedientes-digitales', [
            'alumnos' => $alumnos,
            'metricas' => $metricas,
            'alumnoSeleccionado' => $alumnoSeleccionado,
            'resumenSeleccionado' => $resumenSeleccionado,
            'documentosSeleccionados' => $documentosSeleccionados,
        ]);
    }
}
