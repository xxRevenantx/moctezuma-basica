<?php

namespace App\Livewire\Tutor;

use App\Exceptions\Expedientes\PdfCompatibilityException;
use App\Models\DocumentoTutor;
use App\Models\DocumentoTutorNoAplica;
use App\Models\DocumentoTutorPendienteVincular;
use App\Models\TipoDocumentoTutor;
use App\Models\Tutor;
use App\Services\Expedientes\ExpedienteTutorService;
use App\Services\GestionResponsablesAlumnoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class ExpedienteTutor extends Component
{
    use WithFileUploads;

    public bool $abierto = false;
    public ?int $tutorId = null;

    public bool $mostrarCarga = false;
    public ?int $tipoDocumentoId = null;
    public array $archivos = [];
    public string $contenidoArchivo = 'un_documento';
    public bool $permitirDuplicado = false;
    public ?string $fechaDocumento = null;
    public string $folio = '';
    public string $origen = 'subido';
    public string $observaciones = '';

    public bool $mostrarNoAplica = false;
    public ?int $noAplicaTipoId = null;
    public string $motivoNoAplica = '';

    public array $tipos = [];

    #[On('abrir-expediente-tutor')]
    public function abrir(int $tutorId): void
    {
        $this->autorizarConsulta();
        abort_unless(Tutor::query()->whereKey($tutorId)->exists(), 404);

        $this->tutorId = $tutorId;
        $this->fechaDocumento = now()->toDateString();
        $this->cargarTipos();
        $this->cerrarCarga();
        $this->cerrarNoAplica();
        $this->abierto = true;
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->tutorId = null;
        $this->cerrarCarga();
        $this->cerrarNoAplica();
    }

    public function abrirCarga(int $tipoId): void
    {
        $this->autorizarEdicion();
        $tipo = TipoDocumentoTutor::query()->where('activo', true)->findOrFail($tipoId);

        $this->resetValidation();
        $this->tipoDocumentoId = $tipo->id;
        $this->archivos = [];
        $this->contenidoArchivo = 'un_documento';
        $this->permitirDuplicado = false;
        $this->fechaDocumento = now()->toDateString();
        $this->folio = '';
        $this->origen = 'subido';
        $this->observaciones = '';
        $this->mostrarCarga = true;
    }

    public function cerrarCarga(): void
    {
        $this->mostrarCarga = false;
        $this->tipoDocumentoId = null;
        $this->archivos = [];
        $this->contenidoArchivo = 'un_documento';
        $this->permitirDuplicado = false;
        $this->fechaDocumento = now()->toDateString();
        $this->folio = '';
        $this->origen = 'subido';
        $this->observaciones = '';
        $this->resetValidation();
    }

    public function subirDocumento(): void
    {
        $this->autorizarEdicion();
        $tutor = $this->tutor();
        $maxKb = max((int) config('expedientes_organizador.max_upload_mb', 30), 1) * 1024;

        $this->validate([
            'tipoDocumentoId' => ['required', 'integer', 'exists:tipos_documentos_tutores,id'],
            'archivos' => ['required', 'array', 'min:1', 'max:10'],
            'archivos.*' => [
                'required', 'file', 'mimes:pdf,jpg,jpeg,png,webp',
                'mimetypes:application/pdf,application/x-pdf,image/jpeg,image/png,image/webp',
                'max:' . $maxKb,
            ],
            'contenidoArchivo' => ['required', 'in:un_documento,varios_documentos'],
            'permitirDuplicado' => ['boolean'],
            'fechaDocumento' => ['nullable', 'date'],
            'folio' => ['nullable', 'string', 'max:120'],
            'origen' => ['required', 'in:subido,digitalizado,externo'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ], [
            'archivos.required' => 'Selecciona al menos un archivo.',
            'archivos.max' => 'Puedes cargar hasta 10 archivos en una sola operación.',
            'archivos.*.mimes' => 'Cada documento debe ser PDF, JPG, JPEG, PNG o WEBP.',
            'archivos.*.max' => 'Cada archivo no debe superar los ' . config('expedientes_organizador.max_upload_mb', 30) . ' MB.',
        ]);

        $servicio = app(ExpedienteTutorService::class);
        $fuentePreferidaId = null;
        $archivosGuardados = 0;
        $paginasGuardadas = 0;
        $errores = [];

        foreach ($this->archivos as $archivo) {
            try {
                $resultado = $servicio->registrarFuenteDesdeUpload(
                    $archivo,
                    $tutor,
                    (int) $this->tipoDocumentoId,
                    $this->contenidoArchivo,
                    auth()->id(),
                    $this->permitirDuplicado,
                    [
                        'fecha_documento' => $this->fechaDocumento ?: now()->toDateString(),
                        'folio' => trim($this->folio) ?: null,
                        'origen' => $this->origen,
                        'observaciones' => trim($this->observaciones) ?: null,
                    ]
                );

                $fuentePreferidaId ??= (int) $resultado['fuente']->id;
                $archivosGuardados++;
                $paginasGuardadas += (int) $resultado['paginas'];
            } catch (PdfCompatibilityException|ValidationException $e) {
                $mensaje = $e instanceof ValidationException
                    ? $e->validator->errors()->first()
                    : $e->getMessage();
                $errores[] = $archivo->getClientOriginalName() . ': ' . $mensaje;
            } catch (Throwable $e) {
                report($e);
                $errores[] = $archivo->getClientOriginalName() . ': no fue posible procesar el archivo.';
            }
        }

        if ($archivosGuardados === 0) {
            $this->addError('archivos', implode(' ', $errores) ?: 'No fue posible guardar los archivos.');
            return;
        }

        $this->cerrarCarga();
        $this->dispatch('abrir-organizador-tutor', tutorId: $tutor->id, fuenteId: $fuentePreferidaId);

        $mensaje = "{$archivosGuardados} archivo(s) y {$paginasGuardadas} página(s) quedaron listos para organizar.";
        if ($errores !== []) {
            $mensaje .= ' Algunos archivos no se guardaron: ' . implode(' | ', $errores);
        }

        $this->dispatch(
            'notify',
            type: $errores === [] ? 'success' : 'warning',
            message: $mensaje
        );
    }

    public function abrirOrganizador(?int $fuenteId = null): void
    {
        $this->autorizarEdicion();
        $tutor = $this->tutor();
        $this->dispatch('abrir-organizador-tutor', tutorId: $tutor->id, fuenteId: $fuenteId);
    }

    public function abrirNoAplica(int $tipoId): void
    {
        $this->autorizarEdicion();
        TipoDocumentoTutor::query()->where('activo', true)->findOrFail($tipoId);
        $this->noAplicaTipoId = $tipoId;
        $this->motivoNoAplica = '';
        $this->mostrarNoAplica = true;
        $this->resetValidation();
    }

    public function cerrarNoAplica(): void
    {
        $this->mostrarNoAplica = false;
        $this->noAplicaTipoId = null;
        $this->motivoNoAplica = '';
        $this->resetValidation();
    }

    public function guardarNoAplica(): void
    {
        $this->autorizarEdicion();
        $tutor = $this->tutor();
        $this->validate([
            'noAplicaTipoId' => ['required', 'integer', 'exists:tipos_documentos_tutores,id'],
            'motivoNoAplica' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'motivoNoAplica.required' => 'Escribe el motivo por el que el documento no aplica.',
            'motivoNoAplica.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        $documentoDisponible = DocumentoTutor::query()
            ->where('tutor_id', $tutor->id)
            ->where('tipo_documento_tutor_id', $this->noAplicaTipoId)
            ->where('es_actual', true)
            ->where('es_fuente', false)
            ->whereNotIn('estado', ['rechazado', 'reemplazado', 'cancelado'])
            ->first();

        if ($documentoDisponible?->archivo_existe) {
            $this->addError('motivoNoAplica', 'No puedes marcar como “No aplica” un documento que ya está entregado.');
            return;
        }

        DB::transaction(function () use ($tutor): void {
            DocumentoTutorNoAplica::query()
                ->where('tutor_id', $tutor->id)
                ->where('tipo_documento_tutor_id', $this->noAplicaTipoId)
                ->where('activo', true)
                ->update(['activo' => false, 'updated_at' => now()]);

            DocumentoTutorNoAplica::query()->create([
                'tutor_id' => $tutor->id,
                'tipo_documento_tutor_id' => $this->noAplicaTipoId,
                'motivo' => trim($this->motivoNoAplica),
                'activo' => true,
                'registrado_por' => auth()->id(),
            ]);

            app(ExpedienteTutorService::class)->registrarEvento(
                $tutor,
                'documento_no_aplica',
                'Se marcó un documento esperado como “No aplica”.',
                auth()->id(),
                null,
                null,
                null,
                ['tipo_documento_tutor_id' => $this->noAplicaTipoId, 'motivo' => trim($this->motivoNoAplica)]
            );
        });

        $this->cerrarNoAplica();
        $this->dispatch('notify', type: 'success', message: 'El documento fue marcado como “No aplica”.');
        $this->dispatch('expediente-tutor-actualizado', tutorId: $tutor->id);
    }

    public function quitarNoAplica(int $registroId): void
    {
        $this->autorizarEdicion();
        $tutor = $this->tutor();
        DocumentoTutorNoAplica::query()
            ->where('tutor_id', $tutor->id)
            ->whereKey($registroId)
            ->update(['activo' => false, 'updated_at' => now()]);
        $this->dispatch('notify', type: 'success', message: 'La marca “No aplica” fue retirada.');
    }

    public function actualizarEstado(int $documentoId, string $estado): void
    {
        $this->autorizarEdicion();
        abort_unless(in_array($estado, DocumentoTutor::ESTADOS, true), 422, 'Estado no válido.');
        $tutor = $this->tutor();
        $documento = DocumentoTutor::query()
            ->where('tutor_id', $tutor->id)
            ->where('es_fuente', false)
            ->findOrFail($documentoId);
        abort_unless($documento->es_actual, 422, 'Las versiones históricas no pueden modificarse.');

        $anterior = $documento->toArray();
        $datos = ['estado' => $estado];
        if ($estado === 'validado') {
            $datos['validado_por'] = auth()->id();
            $datos['validado_at'] = now();
        } else {
            $datos['validado_por'] = null;
            $datos['validado_at'] = null;
        }
        if (in_array($estado, ['reemplazado', 'cancelado'], true)) {
            $datos['es_actual'] = false;
        }
        $documento->update($datos);

        app(ExpedienteTutorService::class)->registrarEvento(
            $tutor,
            'estado_actualizado',
            'Se actualizó el estado de un documento del responsable.',
            auth()->id(),
            $documento,
            null,
            $anterior,
            $documento->fresh()->toArray()
        );

        $this->dispatch('notify', type: 'success', message: 'Estado del documento actualizado.');
    }

    public function archivarDocumento(int $documentoId): void
    {
        $this->autorizarEdicion();
        $tutor = $this->tutor();
        $documento = DocumentoTutor::query()
            ->where('tutor_id', $tutor->id)
            ->where('es_fuente', false)
            ->findOrFail($documentoId);
        abort_unless($documento->es_actual, 422, 'El documento ya es histórico.');

        $anterior = $documento->toArray();
        $documento->forceFill(['es_actual' => false, 'estado' => 'cancelado'])->save();
        app(ExpedienteTutorService::class)->registrarEvento(
            $tutor,
            'documento_archivado',
            'El documento dejó de estar vigente, pero su archivo y versión se conservaron.',
            auth()->id(),
            $documento,
            null,
            $anterior,
            $documento->fresh()->toArray()
        );
        $this->dispatch('notify', type: 'success', message: 'Documento archivado sin eliminar su historial.');
    }

    public function vincularLegado(int $pendienteId, ?int $tutorDestinoId = null): void
    {
        $this->autorizarEdicion();
        $tutor = Tutor::query()->findOrFail($tutorDestinoId ?: $this->tutorId);
        $pendiente = DocumentoTutorPendienteVincular::query()->findOrFail($pendienteId);
        app(ExpedienteTutorService::class)->vincularDocumentoLegado($pendiente, $tutor, auth()->id());
        $this->dispatch('notify', type: 'success', message: 'El documento antiguo quedó vinculado al expediente del responsable.');
        $this->dispatch('expediente-tutor-actualizado', tutorId: $tutor->id);
    }

    public function actualizarConfiguracionTipo(int $tipoId, string $campo, bool $valor): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
        abort_unless(in_array($campo, ['activo', 'es_obligatorio'], true), 422);
        TipoDocumentoTutor::query()->whereKey($tipoId)->update([$campo => $valor, 'updated_at' => now()]);
        $this->cargarTipos();
        $this->dispatch('notify', type: 'success', message: 'Configuración documental actualizada.');
    }

    public function actualizarObligatorioParentesco(int $tipoId, string $parentesco, bool $valor): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
        abort_unless(in_array($parentesco, GestionResponsablesAlumnoService::PARENTESCOS, true), 422);

        DB::transaction(function () use ($tipoId, $parentesco, $valor): void {
            $tipo = TipoDocumentoTutor::query()->lockForUpdate()->findOrFail($tipoId);
            $parentescos = collect($tipo->obligatorio_parentescos ?? [])
                ->map(fn ($item): string => (string) $item)
                ->filter(fn (string $item): bool => in_array($item, GestionResponsablesAlumnoService::PARENTESCOS, true));

            $parentescos = $valor
                ? $parentescos->push($parentesco)
                : $parentescos->reject(fn (string $item): bool => $item === $parentesco);

            $tipo->forceFill([
                'obligatorio_parentescos' => $parentescos->unique()->sort()->values()->all(),
            ])->save();
        });

        $this->cargarTipos();
        $this->dispatch('notify', type: 'success', message: 'Obligatoriedad por parentesco actualizada.');
    }

    #[On('organizacion-tutor-confirmada')]
    public function refrescarOrganizacion(int $tutorId): void
    {
        $this->refrescarSiCorresponde($tutorId);
    }

    #[On('expediente-tutor-actualizado')]
    public function refrescarExpediente(int $tutorId): void
    {
        $this->refrescarSiCorresponde($tutorId);
    }

    protected function refrescarSiCorresponde(int $tutorId): void
    {
        if ($this->tutorId !== $tutorId) {
            return;
        }

        $this->cargarTipos();
    }

    protected function cargarTipos(): void
    {
        $this->tipos = TipoDocumentoTutor::query()
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get()
            ->map(fn (TipoDocumentoTutor $tipo): array => [
                'id' => $tipo->id,
                'nombre' => $tipo->nombre,
                'slug' => $tipo->slug,
                'descripcion' => $tipo->descripcion,
                'es_obligatorio' => $tipo->es_obligatorio,
                'obligatorio_parentescos' => $tipo->obligatorio_parentescos ?? [],
                'activo' => $tipo->activo,
            ])->all();
    }

    protected function tutor(): Tutor
    {
        abort_unless($this->tutorId, 422, 'Selecciona un responsable.');

        return Tutor::query()->findOrFail($this->tutorId);
    }

    protected function autorizarConsulta(): void
    {
        abort_unless(
            auth()->user()?->is_admin
                || auth()->user()?->canAccess('alumnos.consultar')
                || auth()->user()?->canAccess('documentos.consultar')
                || auth()->user()?->canAccess('documentos.organizar'),
            403
        );
    }

    protected function autorizarEdicion(): void
    {
        abort_unless(
            auth()->user()?->is_admin
                || auth()->user()?->canAccess('documentos.organizar')
                || auth()->user()?->canAccess('alumnos.editar'),
            403,
            'No tienes permiso para modificar expedientes de responsables.'
        );
    }

    public function render()
    {
        $tutor = null;
        $resumen = null;
        $documentos = collect();
        $fuentes = collect();
        $eventos = collect();
        $pendientes = collect();
        $parentescosConfigurables = GestionResponsablesAlumnoService::PARENTESCOS;

        if ($this->abierto && $this->tutorId) {
            $tutor = Tutor::query()
                ->with([
                    'relacionesActivas.inscripcion' => fn ($query) => $query->withTrashed()->select('inscripciones.id', 'nombre', 'apellido_paterno', 'apellido_materno', 'matricula'),
                    'documentos.tipoDocumento:id,nombre,slug,es_obligatorio,orden',
                    'documentos.usuarioQueSubio:id,name',
                    'documentos.usuarioQueValido:id,name',
                    'documentosNoAplican' => fn ($query) => $query->where('activo', true),
                ])
                ->find($this->tutorId);

            if ($tutor) {
                $resumen = app(ExpedienteTutorService::class)->resumen($tutor);
                $documentos = $tutor->documentos
                    ->where('es_fuente', false)
                    ->sort(function (DocumentoTutor $a, DocumentoTutor $b): int {
                        return (($a->tipoDocumento?->orden ?? 999) <=> ($b->tipoDocumento?->orden ?? 999))
                            ?: ($b->version <=> $a->version)
                            ?: ($b->id <=> $a->id);
                    })->values();
                $fuentes = $tutor->fuentesDocumentales()
                    ->with('usuario:id,name')
                    ->latest()
                    ->get();
                $eventos = $tutor->eventosDocumentales()
                    ->with('usuario:id,name')
                    ->latest()
                    ->limit(30)
                    ->get();

                $inscripcionesIds = $tutor->relaciones()->pluck('inscripcion_id');
                $pendientes = DocumentoTutorPendienteVincular::query()
                    ->where('estado', 'pendiente')
                    ->where(function ($query) use ($tutor, $inscripcionesIds): void {
                        $query->where('tutor_sugerido_id', $tutor->id)
                            ->orWhereIn('inscripcion_id', $inscripcionesIds);
                    })
                    ->with([
                        'documentoAlumno.tipoDocumento:id,nombre,slug',
                        'inscripcion:id,nombre,apellido_paterno,apellido_materno,matricula',
                        'tutorSugerido:id,nombre,apellido_paterno,apellido_materno',
                    ])
                    ->latest()
                    ->get();
            }
        }

        return view('livewire.tutor.expediente-tutor', compact(
            'tutor',
            'resumen',
            'documentos',
            'fuentes',
            'eventos',
            'pendientes',
            'parentescosConfigurables'
        ));
    }
}
