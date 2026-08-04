<?php

namespace App\Livewire\Tutor;

use App\Exceptions\Expedientes\PdfCompatibilityException;
use App\Models\DocumentoTutor;
use App\Models\DocumentoTutorFuente;
use App\Models\DocumentoTutorNoAplica;
use App\Models\OrganizacionDocumentoTutor;
use App\Models\TipoDocumentoTutor;
use App\Models\Tutor;
use App\Services\Expedientes\ExpedienteTutorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class CargaDocumentoTutor extends Component
{
    use WithFileUploads;

    public $archivo = null;

    public int $tutorId;
    public int $tipoDocumentoId;
    public string $etiqueta = '';
    public string $descripcion = '';
    public bool $obligatorio = false;
    public bool $soloLectura = false;
    public int $alumnosRelacionados = 0;

    public bool $guardado = false;
    public bool $inconsistente = false;
    public bool $requiereConfirmacion = false;
    public bool $organizacionPendiente = false;
    public bool $tieneFuentes = false;
    public bool $noAplica = false;
    public ?int $noAplicaId = null;
    public string $motivoNoAplica = '';
    public bool $solicitarMotivoNoAplica = false;
    public string $motivoNoAplicaEntrada = '';

    public ?int $documentoId = null;
    public ?string $archivoGuardadoUrl = null;
    public ?string $archivoDescargaUrl = null;
    public string $nombreArchivo = '';
    public string $tamanoArchivo = '';
    public int $paginasDocumento = 0;
    public string $archivoMimeType = 'application/pdf';
    public int $archivoPaginas = 0;
    public int $maxMb = 30;
    public string $estadoDocumento = 'pendiente';
    public string $mensaje = '';
    public array $historial = [];

    public bool $pdfRequiereDecision = false;
    public bool $pdfPuedeGuardarseOriginal = false;
    public string $pdfDiagnostico = '';

    public function mount(
        int $tutorId,
        int $tipoDocumentoId,
        ?string $etiqueta = null,
        ?string $descripcion = null,
        bool $obligatorio = false,
        bool $soloLectura = false,
    ): void {
        $this->tutorId = $tutorId;
        $this->tipoDocumentoId = $tipoDocumentoId;
        $this->soloLectura = $soloLectura;
        $this->maxMb = max((int) config('expedientes_organizador.max_upload_mb', 30), 1);

        $tipo = TipoDocumentoTutor::query()->where('activo', true)->findOrFail($tipoDocumentoId);
        $tutor = $this->tutor();

        $this->etiqueta = filled($etiqueta) ? trim((string) $etiqueta) : $tipo->nombre;
        $this->descripcion = filled($descripcion)
            ? trim((string) $descripcion)
            : (string) ($tipo->descripcion ?: 'Documento del expediente del responsable.');
        $this->obligatorio = $obligatorio || (bool) $tipo->es_obligatorio;
        $this->alumnosRelacionados = $tutor->relacionesActivas()->count();

        $this->cargarEstado();
    }

    public function updatedArchivo(): void
    {
        $this->resetErrorBag('archivo');
        $this->mensaje = '';
        $this->requiereConfirmacion = false;
        $this->archivoPaginas = 0;
        $this->reiniciarCompatibilidadPdf();
        $this->autorizarModificacion();
        $this->validarArchivo();

        try {
            $inspeccion = app(ExpedienteTutorService::class)->inspeccionarArchivoSubido($this->archivo);
            $this->archivoPaginas = (int) $inspeccion['paginas'];
        } catch (PdfCompatibilityException $e) {
            $this->pdfDiagnostico = $e->getMessage();
            $this->pdfPuedeGuardarseOriginal = $e->canStoreOriginal;
            $this->pdfRequiereDecision = $e->canStoreOriginal;

            if (! $e->canStoreOriginal) {
                $this->addError('archivo', $e->getMessage());
            }

            return;
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            $this->addError('archivo', app()->isLocal()
                ? 'No fue posible inspeccionar el archivo: ' . $e->getMessage()
                : 'No fue posible inspeccionar el archivo seleccionado.');

            return;
        }

        if ($this->documentoId !== null) {
            $this->requiereConfirmacion = true;

            return;
        }

        $this->guardarArchivo('agregar');
    }

    public function guardarArchivo(string $modo = 'reemplazar'): void
    {
        $this->autorizarModificacion();
        $this->validarArchivo();

        if (! in_array($modo, ['agregar', 'reemplazar'], true)) {
            $this->addError('archivo', 'Selecciona una acción válida para integrar el archivo.');

            return;
        }

        try {
            $resultado = app(ExpedienteTutorService::class)->registrarFuenteDesdeUpload(
                $this->archivo,
                $this->tutor(),
                $this->tipoDocumentoId,
                'un_documento',
                auth()->id(),
                false,
                [
                    'fecha_documento' => now()->toDateString(),
                    'origen' => 'subido',
                ],
                $modo,
                false,
            );

            $this->desactivarNoAplica();
            $fuenteId = (int) $resultado['fuente']->id;
            $paginas = (int) ($resultado['paginas'] ?? 1);
            $autoConfirmado = (bool) ($resultado['auto_confirmado'] ?? false);
            $normalizado = (bool) ($resultado['normalizado'] ?? false);
            $normalizador = (string) ($resultado['normalizador'] ?? '');

            $this->limpiarArchivoTemporal();

            if ($autoConfirmado) {
                $this->mensaje = $modo === 'reemplazar'
                    ? 'Documento reemplazado y confirmado. La versión anterior permanece en el historial.'
                    : 'Documento guardado y confirmado correctamente.';
                $this->dispatch('organizacion-tutor-confirmada', tutorId: $this->tutorId);
                $this->dispatch('expediente-tutor-actualizado', tutorId: $this->tutorId);
                $this->resaltarTarjeta();
                $this->dispatch('notify', type: 'success', message: $this->mensaje);
            } else {
                $this->mensaje = $normalizado
                    ? "El archivo se normalizó con {$normalizador}. Revisa y confirma la clasificación de sus {$paginas} página(s)."
                    : "El archivo de {$paginas} página(s) quedó listo. Revisa su clasificación antes de confirmar.";
                $this->dispatch('organizacion-tutor-borrador-actualizado', tutorId: $this->tutorId);
                $this->dispatch('abrir-organizador-tutor', tutorId: $this->tutorId, fuenteId: $fuenteId);
                $this->dispatch('notify', type: 'info', message: $this->mensaje);
            }

            $this->cargarEstado(false);
        } catch (PdfCompatibilityException $e) {
            $this->pdfDiagnostico = $e->getMessage();
            $this->pdfPuedeGuardarseOriginal = $e->canStoreOriginal;
            $this->pdfRequiereDecision = $e->canStoreOriginal;

            if (! $e->canStoreOriginal) {
                $this->addError('archivo', $e->getMessage());
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            $this->addError('archivo', app()->isLocal()
                ? 'No fue posible guardar el documento: ' . $e->getMessage()
                : 'No fue posible guardar el documento. Inténtalo nuevamente.');
        }
    }

    public function guardarOriginalSinOrganizar(): void
    {
        $this->autorizarModificacion();

        if (! $this->pdfPuedeGuardarseOriginal || ! $this->archivo) {
            $this->addError('archivo', 'Selecciona nuevamente el PDF antes de conservarlo sin organizar.');

            return;
        }

        try {
            app(ExpedienteTutorService::class)->registrarFuenteDesdeUpload(
                $this->archivo,
                $this->tutor(),
                $this->tipoDocumentoId,
                'un_documento',
                auth()->id(),
                false,
                [
                    'fecha_documento' => now()->toDateString(),
                    'origen' => 'subido',
                ],
                $this->documentoId !== null ? 'reemplazar' : 'agregar',
                true,
            );

            $this->desactivarNoAplica();
            $this->limpiarArchivoTemporal();
            $this->mensaje = 'El archivo original se conservó como documento recibido en modo de solo lectura.';
            $this->dispatch('expediente-tutor-actualizado', tutorId: $this->tutorId);
            $this->resaltarTarjeta();
            $this->dispatch('notify', type: 'success', message: $this->mensaje);
            $this->cargarEstado(false);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            $this->addError('archivo', app()->isLocal()
                ? 'No fue posible conservar el PDF: ' . $e->getMessage()
                : 'No fue posible conservar el PDF original.');
        }
    }

    public function cancelarReemplazo(): void
    {
        $this->limpiarArchivoTemporal();
        $this->mensaje = 'Carga cancelada. El documento actual no fue modificado.';
    }

    public function abrirOrganizador(): void
    {
        $this->autorizarModificacion();
        $this->dispatch('abrir-organizador-tutor', tutorId: $this->tutorId, fuenteId: null);
    }

    public function solicitarNoAplica(): void
    {
        $this->autorizarModificacion();
        $this->solicitarMotivoNoAplica = true;
        $this->motivoNoAplicaEntrada = '';
        $this->resetErrorBag('motivoNoAplicaEntrada');
    }

    public function cancelarNoAplica(): void
    {
        $this->solicitarMotivoNoAplica = false;
        $this->motivoNoAplicaEntrada = '';
        $this->resetErrorBag('motivoNoAplicaEntrada');
    }

    public function guardarNoAplica(): void
    {
        $this->autorizarModificacion();
        $this->validate([
            'motivoNoAplicaEntrada' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'motivoNoAplicaEntrada.required' => 'Escribe el motivo por el que el documento no aplica.',
            'motivoNoAplicaEntrada.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        $documentoDisponible = $this->consultaDocumentos()
            ->where('es_actual', true)
            ->whereNotIn('estado', ['rechazado', 'reemplazado', 'cancelado'])
            ->first();

        if ($documentoDisponible?->archivo_existe) {
            $this->addError('motivoNoAplicaEntrada', 'No puedes marcar como “No aplica” un documento que ya está entregado.');

            return;
        }

        $tutor = $this->tutor();
        DB::transaction(function () use ($tutor): void {
            DocumentoTutorNoAplica::query()
                ->where('tutor_id', $this->tutorId)
                ->where('tipo_documento_tutor_id', $this->tipoDocumentoId)
                ->where('activo', true)
                ->update(['activo' => false, 'updated_at' => now()]);

            DocumentoTutorNoAplica::query()->create([
                'tutor_id' => $this->tutorId,
                'tipo_documento_tutor_id' => $this->tipoDocumentoId,
                'motivo' => trim($this->motivoNoAplicaEntrada),
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
                [
                    'tipo_documento_tutor_id' => $this->tipoDocumentoId,
                    'motivo' => trim($this->motivoNoAplicaEntrada),
                ]
            );
        });

        $this->cancelarNoAplica();
        $this->mensaje = 'El documento fue marcado como “No aplica”.';
        $this->dispatch('expediente-tutor-actualizado', tutorId: $this->tutorId);
        $this->resaltarTarjeta();
        $this->dispatch('notify', type: 'success', message: $this->mensaje);
        $this->cargarEstado(false);
    }

    public function quitarNoAplica(): void
    {
        $this->autorizarModificacion();

        DocumentoTutorNoAplica::query()
            ->where('tutor_id', $this->tutorId)
            ->where('tipo_documento_tutor_id', $this->tipoDocumentoId)
            ->where('activo', true)
            ->update(['activo' => false, 'updated_at' => now()]);

        $this->mensaje = 'La marca “No aplica” fue retirada.';
        $this->dispatch('expediente-tutor-actualizado', tutorId: $this->tutorId);
        $this->resaltarTarjeta();
        $this->dispatch('notify', type: 'success', message: $this->mensaje);
        $this->cargarEstado(false);
    }

    public function actualizarEstado(string $estado): void
    {
        $this->autorizarModificacion();
        abort_unless(in_array($estado, ['recibido', 'validado', 'rechazado'], true), 422, 'Estado no válido.');
        abort_unless($this->documentoId, 422, 'No existe un documento vigente.');

        $tutor = $this->tutor();
        $documento = $this->consultaDocumentos()->whereKey($this->documentoId)->firstOrFail();
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

        $this->mensaje = 'Estado del documento actualizado.';
        $this->dispatch('expediente-tutor-actualizado', tutorId: $this->tutorId);
        $this->resaltarTarjeta();
        $this->dispatch('notify', type: 'success', message: $this->mensaje);
        $this->cargarEstado(false);
    }

    public function archivarDocumento(): void
    {
        $this->autorizarModificacion();
        abort_unless($this->documentoId, 422, 'No existe un documento vigente.');

        $tutor = $this->tutor();
        $documento = $this->consultaDocumentos()->whereKey($this->documentoId)->firstOrFail();
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

        $this->mensaje = 'Documento archivado sin eliminar su historial.';
        $this->dispatch('expediente-tutor-actualizado', tutorId: $this->tutorId);
        $this->resaltarTarjeta();
        $this->dispatch('notify', type: 'success', message: $this->mensaje);
        $this->cargarEstado(false);
    }

    #[On('organizacion-tutor-confirmada')]
    public function organizacionConfirmada(int $tutorId): void
    {
        if ($tutorId === $this->tutorId) {
            $this->cargarEstado(false);
        }
    }

    #[On('organizacion-tutor-borrador-actualizado')]
    public function organizacionBorradorActualizado(int $tutorId): void
    {
        if ($tutorId === $this->tutorId) {
            $this->cargarEstado(false);
        }
    }

    #[On('expediente-tutor-actualizado')]
    public function expedienteActualizado(int $tutorId): void
    {
        if ($tutorId === $this->tutorId) {
            $this->cargarEstado(false);
        }
    }

    protected function cargarEstado(bool $limpiarMensaje = true): void
    {
        $documento = $this->consultaDocumentos()
            ->where('es_actual', true)
            ->latest('version')
            ->first();

        $this->guardado = false;
        $this->inconsistente = false;
        $this->documentoId = null;
        $this->archivoGuardadoUrl = null;
        $this->archivoDescargaUrl = null;
        $this->nombreArchivo = '';
        $this->tamanoArchivo = '';
        $this->paginasDocumento = 0;
        $this->archivoMimeType = 'application/pdf';
        $this->estadoDocumento = $documento?->estado ?: 'pendiente';

        if ($documento) {
            $this->documentoId = $documento->id;
            $this->inconsistente = ! $documento->archivo_existe;
            $this->guardado = ! $this->inconsistente
                && ! in_array($documento->estado, ['pendiente', 'rechazado', 'reemplazado', 'cancelado'], true);

            if (! $this->inconsistente) {
                $this->archivoGuardadoUrl = route('misrutas.expedientes-tutores.preview', $documento);
                $this->archivoDescargaUrl = route('misrutas.expedientes-tutores.download', $documento);
            }

            $this->nombreArchivo = (string) $documento->nombre_original;
            $this->tamanoArchivo = $documento->tamano_legible;
            $this->paginasDocumento = max((int) ($documento->paginas_total ?? 0), 0);
            $this->archivoMimeType = (string) ($documento->mime_type ?: 'application/pdf');
        }

        $noAplica = DocumentoTutorNoAplica::query()
            ->where('tutor_id', $this->tutorId)
            ->where('tipo_documento_tutor_id', $this->tipoDocumentoId)
            ->where('activo', true)
            ->latest('id')
            ->first();

        $this->noAplica = (bool) $noAplica;
        $this->noAplicaId = $noAplica?->id;
        $this->motivoNoAplica = (string) ($noAplica?->motivo ?: '');

        $borrador = OrganizacionDocumentoTutor::query()
            ->where('tutor_id', $this->tutorId)
            ->where('estado', 'borrador')
            ->latest('version')
            ->first();
        $this->organizacionPendiente = $this->borradorModificaEsteTipo($borrador);

        $this->tieneFuentes = DocumentoTutorFuente::query()
            ->where('tutor_id', $this->tutorId)
            ->where('estado', 'activo')
            ->where('protegido', false)
            ->exists();

        $this->historial = $this->consultaDocumentos()
            ->latest('version')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (DocumentoTutor $item): array => [
                'id' => $item->id,
                'version' => (int) $item->version,
                'estado' => (string) $item->estado,
                'fecha' => $item->created_at?->format('d/m/Y H:i') ?: 'Sin fecha',
                'nombre' => (string) $item->nombre_original,
                'tamano' => $item->tamano_legible,
                'paginas' => max((int) ($item->paginas_total ?? 0), 0),
                'mime' => (string) ($item->mime_type ?: 'application/pdf'),
                'actual' => (bool) $item->es_actual,
                'disponible' => $item->archivo_existe,
                'url' => $item->archivo_existe
                    ? route('misrutas.expedientes-tutores.preview', $item)
                    : null,
                'download_url' => $item->archivo_existe
                    ? route('misrutas.expedientes-tutores.download', $item)
                    : null,
            ])
            ->all();

        if ($limpiarMensaje) {
            $this->mensaje = '';
        }
    }

    protected function borradorModificaEsteTipo(?OrganizacionDocumentoTutor $borrador): bool
    {
        if (! $borrador) {
            return false;
        }

        $actual = collect($borrador->asignaciones ?? [])
            ->filter(fn (array $item): bool => (int) ($item['tipo_documento_tutor_id'] ?? 0) === $this->tipoDocumentoId)
            ->map(fn (array $item): array => [
                'fuente_id' => (int) ($item['fuente_id'] ?? 0),
                'pagina' => (int) ($item['pagina'] ?? 0),
                'orden' => (int) ($item['orden'] ?? 0),
                'rotacion' => (int) ($item['rotacion'] ?? 0),
            ])
            ->sortBy(fn (array $item): string => $item['fuente_id'] . ':' . $item['pagina'])
            ->values()
            ->all();

        $baseline = collect((array) data_get($borrador->metadatos, 'baseline_asignaciones', []))
            ->filter(fn (array $item): bool => (int) ($item['tipo_documento_tutor_id'] ?? 0) === $this->tipoDocumentoId)
            ->map(fn (array $item): array => [
                'fuente_id' => (int) ($item['fuente_id'] ?? 0),
                'pagina' => (int) ($item['pagina'] ?? 0),
                'orden' => (int) ($item['orden'] ?? 0),
                'rotacion' => (int) ($item['rotacion'] ?? 0),
            ])
            ->sortBy(fn (array $item): string => $item['fuente_id'] . ':' . $item['pagina'])
            ->values()
            ->all();

        return $actual !== $baseline;
    }

    protected function resaltarTarjeta(): void
    {
        $this->dispatch(
            'resaltar-documento-tutor',
            tutorId: $this->tutorId,
            tipoDocumentoId: $this->tipoDocumentoId,
        );
    }

    protected function validarArchivo(): void
    {
        $maxKb = $this->maxMb * 1024;

        $this->validate([
            'archivo' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'mimetypes:application/pdf,application/x-pdf,image/jpeg,image/png,image/webp',
                'max:' . $maxKb,
            ],
        ], [
            'archivo.required' => 'Selecciona un archivo.',
            'archivo.file' => 'El archivo seleccionado no es válido.',
            'archivo.mimes' => 'Solo se aceptan archivos PDF, JPG, JPEG, PNG o WEBP.',
            'archivo.mimetypes' => 'La firma interna del archivo no corresponde a un formato permitido.',
            'archivo.max' => "El archivo no debe superar {$this->maxMb} MB.",
        ]);
    }

    protected function consultaDocumentos()
    {
        return DocumentoTutor::query()
            ->where('tutor_id', $this->tutorId)
            ->where('tipo_documento_tutor_id', $this->tipoDocumentoId)
            ->where('es_fuente', false);
    }

    protected function desactivarNoAplica(): void
    {
        DocumentoTutorNoAplica::query()
            ->where('tutor_id', $this->tutorId)
            ->where('tipo_documento_tutor_id', $this->tipoDocumentoId)
            ->where('activo', true)
            ->update(['activo' => false, 'updated_at' => now()]);
    }

    protected function limpiarArchivoTemporal(): void
    {
        $this->reset('archivo');
        $this->archivoPaginas = 0;
        $this->requiereConfirmacion = false;
        $this->reiniciarCompatibilidadPdf();
    }

    protected function reiniciarCompatibilidadPdf(): void
    {
        $this->pdfRequiereDecision = false;
        $this->pdfPuedeGuardarseOriginal = false;
        $this->pdfDiagnostico = '';
    }

    protected function autorizarModificacion(): void
    {
        $this->autorizarLectura();
        abort_if($this->soloLectura, 422, 'Este expediente es únicamente de consulta.');
        abort_unless(
            auth()->user()?->is_admin
                || auth()->user()?->canAccess('documentos.organizar')
                || auth()->user()?->canAccess('alumnos.editar'),
            403,
            'No tienes permiso para modificar expedientes de responsables.'
        );
    }

    protected function autorizarLectura(): void
    {
        abort_unless(
            auth()->check()
                && (
                    auth()->user()->is_admin
                    || auth()->user()->canAccess('alumnos.consultar')
                    || auth()->user()->canAccess('documentos.consultar')
                    || auth()->user()->canAccess('documentos.organizar')
                ),
            403,
            'No tienes permiso para consultar expedientes de responsables.'
        );
    }

    protected function tutor(): Tutor
    {
        return Tutor::query()->findOrFail($this->tutorId);
    }

    public function render()
    {
        $this->autorizarLectura();

        return view('livewire.tutor.carga-documento-tutor');
    }
}
