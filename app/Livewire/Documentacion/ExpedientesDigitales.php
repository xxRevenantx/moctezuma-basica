<?php

namespace App\Livewire\Documentacion;

use App\Models\CicloEscolar;
use App\Models\DocumentoAlumno;
use App\Models\DocumentoAlumnoFuente;
use App\Models\DocumentoAlumnoNoAplica;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\TipoDocumento;
use App\Services\ExpedienteDigitalService;
use App\Services\Expedientes\OrganizadorExpedienteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
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
    public string $modo_integracion = 'agregar';
    public string $contenido_archivo = 'un_documento';
    public bool $permitir_archivo_duplicado = false;

    public bool $mostrarNoAplica = false;
    public ?int $no_aplica_tipo_id = null;
    public ?int $no_aplica_nivel_id = null;
    public ?int $no_aplica_grado_id = null;
    public ?int $no_aplica_ciclo_id = null;
    public string $no_aplica_motivo = '';

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

    public function abrirOrganizador(?int $fuenteId = null): void
    {
        $this->autorizarAdmin();
        abort_unless($this->alumnoSeleccionadoId, 422, 'Selecciona un alumno.');
        $this->asegurarAlumnoModificable();

        $this->dispatch(
            'abrir-organizador-expediente',
            inscripcionId: $this->alumnoSeleccionadoId,
            fuenteId: $fuenteId
        );
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
        $this->contenido_archivo = 'un_documento';
        $this->permitir_archivo_duplicado = false;

        if (!$tipo->requiere_nivel) {
            $this->nivel_certificado_id = null;
            $this->grado_documento_id = null;
            $this->ciclo_escolar_documento_id = null;
        }

        $this->actualizarIndicadorReemplazo();
        $this->modo_integracion = $this->reemplazaDocumentoActual ? 'reemplazar' : 'agregar';
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
        $this->modo_integracion = 'agregar';
        $this->contenido_archivo = 'un_documento';
        $this->permitir_archivo_duplicado = false;
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
        ]);

        $tipo = TipoDocumento::query()
            ->where('activo', true)
            ->findOrFail($this->tipo_documento_id);

        $esAcademico = in_array($tipo->slug, [
            'boleta-final-grado',
            'constancia-estudios',
            'constancia-baja-traslado',
            'constancia-traslado-calificaciones',
        ], true);
        $maxKb = max((int) config('expedientes_organizador.max_upload_mb', 30), 1) * 1024;

        $reglas = [
            'tipo_documento_id' => ['required', 'integer', 'exists:tipos_documentos,id'],
            'archivo' => [
                'required', 'file', 'mimes:pdf,jpg,jpeg,png,webp',
                'mimetypes:application/pdf,application/x-pdf,image/jpeg,image/png,image/webp',
                'max:' . $maxKb,
            ],
            'fecha_documento' => ['nullable', 'date'],
            'folio_documento' => ['nullable', 'string', 'max:100'],
            'origen_documento' => ['required', 'in:externo,subido,digitalizado'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'modo_integracion' => ['required', 'in:agregar,reemplazar'],
            'contenido_archivo' => ['required', 'in:un_documento,varios_documentos'],
            'permitir_archivo_duplicado' => ['boolean'],
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
            'archivo.required' => 'Selecciona un archivo.',
            'archivo.mimes' => 'El documento debe ser PDF, JPG, JPEG, PNG o WEBP.',
            'archivo.mimetypes' => 'El archivo seleccionado no tiene un formato permitido.',
            'archivo.max' => 'El archivo no debe superar los ' . config('expedientes_organizador.max_upload_mb', 30) . ' MB.',
            'nivel_certificado_id.required' => 'Selecciona el nivel relacionado con el documento.',
            'grado_documento_id.required' => 'Selecciona el grado relacionado con el documento.',
            'ciclo_escolar_documento_id.required' => 'Selecciona el ciclo escolar del documento.',
            'motivo_documento.required' => 'Escribe el motivo de la baja, traslado o movimiento.',
            'modo_integracion.required' => 'Selecciona si deseas agregar o reemplazar páginas.',
            'contenido_archivo.required' => 'Indica si el archivo contiene uno o varios documentos.',
        ]);

        $nivelId = $tipo->requiere_nivel ? $this->nivel_certificado_id : null;
        $gradoId = $esAcademico ? $this->grado_documento_id : null;
        $grupoId = $esAcademico ? $this->grupo_documento_id : null;
        $cicloEscolarId = $esAcademico ? $this->ciclo_escolar_documento_id : null;

        if ($esAcademico) {
            $gradoValido = Grado::query()->whereKey($gradoId)->where('nivel_id', $nivelId)->exists();
            if (! $gradoValido) {
                $this->addError('grado_documento_id', 'El grado no pertenece al nivel seleccionado.');
                return;
            }

            if ($grupoId && ! Grupo::query()->whereKey($grupoId)->where('nivel_id', $nivelId)->where('grado_id', $gradoId)->exists()) {
                $this->addError('grupo_documento_id', 'El grupo no pertenece al nivel y grado seleccionados.');
                return;
            }
        }

        if ($tipo->slug === 'boleta-final-grado') {
            $nivel = collect($this->niveles)->firstWhere('id', $nivelId);
            $textoNivel = Str::lower(trim(($nivel['slug'] ?? '') . ' ' . ($nivel['nombre'] ?? '')));
            if (! Str::contains($textoNivel, ['primaria', 'secundaria'])) {
                $this->addError('nivel_certificado_id', 'Las boletas finales solo se manejan para primaria y secundaria.');
                return;
            }
        }

        try {
            $alumno = Inscripcion::withTrashed()->findOrFail($this->alumnoSeleccionadoId);
            $resultado = app(OrganizadorExpedienteService::class)->registrarFuenteDesdeUpload(
                $this->archivo,
                $alumno,
                [
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
                    'observaciones' => trim($this->observaciones) ?: null,
                ],
                $this->modo_integracion,
                $this->contenido_archivo,
                auth()->id(),
                $this->permitir_archivo_duplicado
            );

            $fuenteId = $resultado['fuente']->id;
            $paginas = $resultado['paginas'];
            $this->cerrarCarga();
            $this->dispatch('abrir-organizador-expediente', inscripcionId: $alumno->id, fuenteId: $fuenteId);
            $this->dispatch(
                'notify',
                type: 'success',
                message: "Archivo fuente guardado con {$paginas} página(s). Confirma su organización para marcar los documentos como entregados."
            );
        } catch (ValidationException $e) {
            $this->addError('archivo', $e->validator->errors()->first());
        } catch (Throwable $e) {
            report($e);
            $this->addError('archivo', app()->environment('local')
                ? 'No fue posible preparar el archivo: ' . $e->getMessage()
                : 'No fue posible preparar el archivo. Inténtalo nuevamente.');
        }
    }

    public function abrirNoAplica(int $tipoId, ?int $nivelId = null, ?int $gradoId = null, ?int $cicloId = null): void
    {
        $this->autorizarAdmin();
        abort_unless($this->alumnoSeleccionadoId, 422);
        $this->asegurarAlumnoModificable();
        TipoDocumento::query()->where('activo', true)->findOrFail($tipoId);
        $this->no_aplica_tipo_id = $tipoId;
        $this->no_aplica_nivel_id = $nivelId;
        $this->no_aplica_grado_id = $gradoId;
        $this->no_aplica_ciclo_id = $cicloId;
        $this->no_aplica_motivo = '';
        $this->mostrarNoAplica = true;
        $this->resetValidation();
    }

    public function cerrarNoAplica(): void
    {
        $this->mostrarNoAplica = false;
        $this->no_aplica_tipo_id = null;
        $this->no_aplica_nivel_id = null;
        $this->no_aplica_grado_id = null;
        $this->no_aplica_ciclo_id = null;
        $this->no_aplica_motivo = '';
        $this->resetValidation();
    }

    public function guardarNoAplica(): void
    {
        $this->autorizarAdmin();
        $this->asegurarAlumnoModificable();
        $this->validate([
            'no_aplica_tipo_id' => ['required', 'integer', 'exists:tipos_documentos,id'],
            'no_aplica_motivo' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'no_aplica_motivo.required' => 'Escribe el motivo por el que el documento no aplica.',
            'no_aplica_motivo.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        $documentoDisponible = DocumentoAlumno::query()
            ->where('inscripcion_id', $this->alumnoSeleccionadoId)
            ->where('tipo_documento_id', $this->no_aplica_tipo_id)
            ->where('es_actual', true)
            ->where('es_fuente', false)
            ->whereNotIn('estado', ['rechazado', 'reemplazado', 'cancelada'])
            ->whereNotNull('ruta')
            ->when(
                $this->no_aplica_nivel_id,
                fn (Builder $query) => $query->where('nivel_id', $this->no_aplica_nivel_id),
                fn (Builder $query) => $query->whereNull('nivel_id')
            )
            ->when(
                $this->no_aplica_grado_id,
                fn (Builder $query) => $query->where('grado_id', $this->no_aplica_grado_id),
                fn (Builder $query) => $query->whereNull('grado_id')
            )
            ->when(
                $this->no_aplica_ciclo_id,
                fn (Builder $query) => $query->where('ciclo_escolar_id', $this->no_aplica_ciclo_id),
                fn (Builder $query) => $query->whereNull('ciclo_escolar_id')
            )
            ->exists();

        if ($documentoDisponible) {
            $this->addError('no_aplica_motivo', 'No puedes marcar como “No aplica” un documento que ya está entregado. Primero conserva o reemplaza su versión actual.');
            return;
        }

        DB::transaction(function (): void {
            $query = DocumentoAlumnoNoAplica::query()
                ->where('inscripcion_id', $this->alumnoSeleccionadoId)
                ->where('tipo_documento_id', $this->no_aplica_tipo_id)
                ->where('activo', true);

            foreach ([
                'nivel_id' => $this->no_aplica_nivel_id,
                'grado_id' => $this->no_aplica_grado_id,
                'ciclo_escolar_id' => $this->no_aplica_ciclo_id,
            ] as $campo => $valor) {
                $valor ? $query->where($campo, $valor) : $query->whereNull($campo);
            }

            $query->update(['activo' => false, 'updated_at' => now()]);

            DocumentoAlumnoNoAplica::query()->create([
                'inscripcion_id' => $this->alumnoSeleccionadoId,
                'tipo_documento_id' => $this->no_aplica_tipo_id,
                'nivel_id' => $this->no_aplica_nivel_id,
                'grado_id' => $this->no_aplica_grado_id,
                'ciclo_escolar_id' => $this->no_aplica_ciclo_id,
                'motivo' => trim($this->no_aplica_motivo),
                'activo' => true,
                'registrado_por' => auth()->id(),
            ]);
        });

        $this->cerrarNoAplica();
        $this->dispatch('notify', type: 'success', message: 'El documento fue marcado como “No aplica” con su justificación.');
    }

    public function quitarNoAplica(int $registroId): void
    {
        $this->autorizarAdmin();
        $this->asegurarAlumnoModificable();
        DocumentoAlumnoNoAplica::query()
            ->where('inscripcion_id', $this->alumnoSeleccionadoId)
            ->whereKey($registroId)
            ->update(['activo' => false, 'updated_at' => now()]);
        $this->dispatch('notify', type: 'success', message: 'La marca “No aplica” fue retirada.');
    }

    #[On('organizacion-expediente-confirmada')]
    public function organizacionConfirmada(int $inscripcionId): void
    {
        if ($inscripcionId !== $this->alumnoSeleccionadoId) {
            return;
        }

        // El evento de Livewire provoca el renderizado del componente padre.
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

    public function etiquetaEstadoExpediente(Inscripcion $alumno): ?string
    {
        if ($alumno->trashed() && ! $alumno->esBajaAdministrativa()) {
            return 'Registro archivado — expediente histórico';
        }

        if ($alumno->esEgresado()) {
            return 'Egresado — expediente histórico';
        }

        if ($alumno->esBajaAdministrativa()) {
            return $alumno->etiqueta_estatus . ' — expediente histórico';
        }

        return null;
    }

    public function claseEstadoExpediente(Inscripcion $alumno): string
    {
        if ($alumno->trashed() && ! $alumno->esBajaAdministrativa()) {
            return 'bg-slate-100 text-slate-700 dark:bg-neutral-800 dark:text-slate-300';
        }

        if ($alumno->esEgresado()) {
            return 'bg-violet-50 text-violet-700 dark:bg-violet-950/30 dark:text-violet-300';
        }

        return 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300';
    }

    private function esAlumnoVigente(Inscripcion $alumno): bool
    {
        return ! $alumno->trashed()
            && ! $alumno->esEgresado()
            && ! $alumno->esBajaAdministrativa();
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
                'ciclo_escolar_id',
                'foto_path',
                'activo',
                'estatus',
                'fecha_estatus',
                'motivo_estatus',
                'fecha_baja',
                'motivo_baja',
                'observaciones_baja',
                'fecha_inscripcion',
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
                'documentos.organizacion:id,fuentes_ids',
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
            'completos' => $alumnos->filter(
                fn (Inscripcion $alumno) => $this->esAlumnoVigente($alumno)
                    && $alumno->resumen_documental['completo']
            ),
            'incompletos' => $alumnos->filter(
                fn (Inscripcion $alumno) => $this->esAlumnoVigente($alumno)
                    && ! $alumno->resumen_documental['completo']
            ),
            'egresados' => $alumnos->filter(
                fn (Inscripcion $alumno) => ! $alumno->trashed() && $alumno->esEgresado()
            ),
            'bajas' => $alumnos->filter(
                fn (Inscripcion $alumno) => $alumno->trashed() || $alumno->esBajaAdministrativa()
            ),
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
            ->where('es_fuente', false)
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
            $alumno->expedienteSoloLectura(),
            422,
            'El expediente de un alumno con baja, traslado, suspensión o archivo es únicamente histórico.'
        );
    }

    private function autorizarAdmin(): void
    {
        abort_unless(auth()->check() && (auth()->user()->is_admin || auth()->user()->canAccess('documentos.organizar')), 403, 'No tienes permiso para administrar expedientes digitales.');
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
            'completos' => $todos->filter(
                fn (Inscripcion $alumno) => $this->esAlumnoVigente($alumno)
                    && $alumno->resumen_documental['completo']
            )->count(),
            'incompletos' => $todos->filter(
                fn (Inscripcion $alumno) => $this->esAlumnoVigente($alumno)
                    && ! $alumno->resumen_documental['completo']
            )->count(),
            'egresados' => $todos->filter(
                fn (Inscripcion $alumno) => ! $alumno->trashed() && $alumno->esEgresado()
            )->count(),
            'bajas' => $todos->filter(
                fn (Inscripcion $alumno) => $alumno->trashed() || $alumno->esBajaAdministrativa()
            )->count(),
        ];

        $alumnos = $this->paginar($this->aplicarFiltroEstado($todos)->values());

        $alumnoSeleccionado = null;
        $resumenSeleccionado = null;
        $documentosSeleccionados = collect();
        $fuentesSeleccionadas = collect();

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
                'documentos.organizacion:id,fuentes_ids',
                ])
                ->find($this->alumnoSeleccionadoId);

            if ($alumnoSeleccionado) {
                $resumenSeleccionado = $servicio->resumen($alumnoSeleccionado);
                $documentosSeleccionados = $servicio->documentosOrdenados($alumnoSeleccionado);
                $fuentesSeleccionadas = DocumentoAlumnoFuente::query()
                    ->where('inscripcion_id', $alumnoSeleccionado->id)
                    ->with(['documentoAlumno.tipoDocumento:id,nombre,slug', 'usuario:id,name'])
                    ->orderByDesc('created_at')
                    ->get();
            }
        }

        return view('livewire.documentacion.expedientes-digitales', [
            'alumnos' => $alumnos,
            'metricas' => $metricas,
            'alumnoSeleccionado' => $alumnoSeleccionado,
            'resumenSeleccionado' => $resumenSeleccionado,
            'documentosSeleccionados' => $documentosSeleccionados,
            'fuentesSeleccionadas' => $fuentesSeleccionadas,
        ]);
    }
}
