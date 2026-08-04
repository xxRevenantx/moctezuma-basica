<?php

namespace App\Livewire\Documentacion;

use App\Exceptions\Expedientes\PdfCompatibilityException;
use App\Models\DocumentoAlumno;
use App\Models\DocumentoAlumnoFuente;
use App\Models\DocumentoAlumnoNoAplica;
use App\Models\Inscripcion;
use App\Models\OrganizacionDocumentoAlumno;
use App\Models\TipoDocumento;
use App\Services\Expedientes\OrganizadorExpedienteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class CargaDocumentoExpediente extends Component
{
    use WithFileUploads;

    public $archivo = null;

    public int $inscripcionId;
    public int $tipoDocumentoId;
    public ?int $nivelId = null;
    public ?int $gradoId = null;
    public ?int $grupoId = null;
    public ?int $cicloEscolarId = null;

    public string $etiqueta = '';
    public string $descripcion = '';
    public bool $obligatorio = false;
    public bool $soloLectura = false;

    public bool $guardado = false;
    public bool $inconsistente = false;
    public bool $requiereConfirmacion = false;
    public bool $organizacionPendiente = false;
    public bool $tieneFuentes = false;
    public bool $noAplica = false;
    public ?int $noAplicaId = null;
    public string $motivoNoAplica = '';

    public ?int $documentoId = null;
    public ?string $archivoGuardadoUrl = null;
    public ?string $archivoDescargaUrl = null;
    public string $nombreArchivo = '';
    public string $tamanoArchivo = '';
    public int $paginasDocumento = 0;
    public int $archivoPaginas = 0;
    public int $maxMb = 30;
    public string $estadoDocumento = 'pendiente';
    public string $mensaje = '';
    public array $historial = [];

    public bool $pdfRequiereDecision = false;
    public bool $pdfPuedeGuardarseOriginal = false;
    public string $pdfDiagnostico = '';

    public function mount(
        int $inscripcionId,
        int $tipoDocumentoId,
        ?int $nivelId = null,
        ?int $gradoId = null,
        ?int $grupoId = null,
        ?int $cicloEscolarId = null,
        ?string $etiqueta = null,
        bool $soloLectura = false,
    ): void {
        $this->inscripcionId = $inscripcionId;
        $this->tipoDocumentoId = $tipoDocumentoId;
        $this->nivelId = $nivelId;
        $this->gradoId = $gradoId;
        $this->grupoId = $grupoId;
        $this->cicloEscolarId = $cicloEscolarId;
        $this->soloLectura = $soloLectura;
        $this->maxMb = max((int) config('expedientes_organizador.max_upload_mb', 30), 1);

        $tipo = TipoDocumento::query()->where('activo', true)->findOrFail($tipoDocumentoId);
        $alumno = $this->alumno();
        abort_unless(
            ! $tipo->nivel_aplica_id || (int) $tipo->nivel_aplica_id === (int) $alumno->nivel_id,
            404,
            'El documento solicitado no aplica para el nivel del alumno.'
        );
        $this->etiqueta = filled($etiqueta) ? trim((string) $etiqueta) : $tipo->nombre;
        $this->descripcion = (string) ($tipo->descripcion ?: 'Documento del expediente del alumno.');
        $this->obligatorio = (bool) $tipo->es_obligatorio;

        $this->soloLectura = $this->soloLectura || $alumno->expedienteSoloLectura();

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
            $inspeccion = app(OrganizadorExpedienteService::class)->inspeccionarArchivoSubido($this->archivo);
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

        if ($this->guardado) {
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
            $resultado = app(OrganizadorExpedienteService::class)->registrarFuenteDesdeUpload(
                $this->archivo,
                $this->alumno(),
                $this->contexto(),
                $modo,
                'un_documento',
                auth()->id(),
                false,
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
                $this->dispatch('organizacion-expediente-confirmada', inscripcionId: $this->inscripcionId);
                $this->dispatch('documento-expediente-actualizado', inscripcionId: $this->inscripcionId);
                $this->dispatch('notify', type: 'success', message: $this->mensaje);
            } else {
                $this->mensaje = $normalizado
                    ? "El archivo se normalizó con {$normalizador}. Revisa y confirma la clasificación de sus {$paginas} página(s)."
                    : "El archivo de {$paginas} página(s) quedó listo. Revisa su clasificación antes de confirmar.";
                $this->dispatch('organizacion-expediente-borrador-actualizado', inscripcionId: $this->inscripcionId);
                $this->dispatch(
                    'abrir-organizador-expediente',
                    inscripcionId: $this->inscripcionId,
                    fuenteId: $fuenteId,
                );
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
            app(OrganizadorExpedienteService::class)->registrarFuenteDesdeUpload(
                $this->archivo,
                $this->alumno(),
                $this->contexto(),
                $this->guardado ? 'reemplazar' : 'agregar',
                'un_documento',
                auth()->id(),
                false,
                true,
            );

            $this->desactivarNoAplica();
            $this->limpiarArchivoTemporal();
            $this->mensaje = 'El archivo original se conservó como documento recibido en modo de solo lectura.';
            $this->dispatch('documento-expediente-actualizado', inscripcionId: $this->inscripcionId);
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
        $this->dispatch(
            'abrir-organizador-expediente',
            inscripcionId: $this->inscripcionId,
            fuenteId: null,
        );
    }

    public function solicitarNoAplica(): void
    {
        $this->autorizarModificacion();
        $this->dispatch(
            'solicitar-no-aplica-expediente',
            inscripcionId: $this->inscripcionId,
            tipoId: $this->tipoDocumentoId,
            nivelId: $this->nivelId,
            gradoId: $this->gradoId,
            cicloId: $this->cicloEscolarId,
        );
    }

    public function quitarNoAplica(): void
    {
        $this->autorizarModificacion();

        DocumentoAlumnoNoAplica::query()
            ->where('inscripcion_id', $this->inscripcionId)
            ->where('tipo_documento_id', $this->tipoDocumentoId)
            ->where(fn (Builder $query) => $this->aplicarContextoNoAplica($query))
            ->where('activo', true)
            ->update(['activo' => false, 'updated_at' => now()]);

        $this->mensaje = 'La marca “No aplica” fue retirada.';
        $this->dispatch('documento-expediente-actualizado', inscripcionId: $this->inscripcionId);
        $this->dispatch('notify', type: 'success', message: $this->mensaje);
        $this->cargarEstado(false);
    }

    #[On('organizacion-expediente-confirmada')]
    public function organizacionConfirmada(int $inscripcionId): void
    {
        if ($inscripcionId === $this->inscripcionId) {
            $this->cargarEstado(false);
        }
    }

    #[On('organizacion-expediente-borrador-actualizado')]
    public function organizacionBorradorActualizado(int $inscripcionId): void
    {
        if ($inscripcionId === $this->inscripcionId) {
            $this->cargarEstado(false);
        }
    }

    #[On('documento-expediente-actualizado')]
    public function documentoActualizado(int $inscripcionId): void
    {
        if ($inscripcionId === $this->inscripcionId) {
            $this->cargarEstado(false);
        }
    }

    protected function cargarEstado(bool $limpiarMensaje = true): void
    {
        $documento = $this->consultaDocumentos()->where('es_actual', true)->latest('version')->first();

        $this->guardado = false;
        $this->inconsistente = false;
        $this->documentoId = null;
        $this->archivoGuardadoUrl = null;
        $this->archivoDescargaUrl = null;
        $this->nombreArchivo = '';
        $this->tamanoArchivo = '';
        $this->paginasDocumento = 0;
        $this->estadoDocumento = $documento?->estado ?: 'pendiente';

        if ($documento) {
            $this->documentoId = $documento->id;
            $this->inconsistente = ! $documento->archivo_existe;
            $this->guardado = ! $this->inconsistente
                && ! in_array($documento->estado, ['pendiente', 'rechazado', 'reemplazado', 'cancelada'], true);

            if (! $this->inconsistente) {
                $this->archivoGuardadoUrl = route('misrutas.expedientes.preview', $documento);
                $this->archivoDescargaUrl = route('misrutas.expedientes.download', $documento);
            }

            $this->nombreArchivo = (string) $documento->nombre_original;
            $this->tamanoArchivo = $documento->tamano_legible;
            $this->paginasDocumento = max((int) ($documento->paginas_total ?? 0), 0);
        }

        $noAplica = DocumentoAlumnoNoAplica::query()
            ->where('inscripcion_id', $this->inscripcionId)
            ->where('tipo_documento_id', $this->tipoDocumentoId)
            ->where(fn (Builder $query) => $this->aplicarContextoNoAplica($query))
            ->where('activo', true)
            ->latest('id')
            ->first();

        $this->noAplica = (bool) $noAplica;
        $this->noAplicaId = $noAplica?->id;
        $this->motivoNoAplica = (string) ($noAplica?->motivo ?: '');

        $borrador = OrganizacionDocumentoAlumno::query()
            ->where('inscripcion_id', $this->inscripcionId)
            ->where('estado', 'borrador')
            ->latest('version')
            ->first();
        $this->organizacionPendiente = (bool) $borrador
            && collect($borrador->asignaciones ?? [])->contains(
                fn (array $asignacion): bool => $this->asignacionPerteneceAlContexto($asignacion)
            );

        $this->tieneFuentes = DocumentoAlumnoFuente::query()
            ->where('inscripcion_id', $this->inscripcionId)
            ->where('estado', 'activo')
            ->where('protegido', false)
            ->exists();

        $this->historial = $this->consultaDocumentos()
            ->latest('version')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (DocumentoAlumno $item): array => [
                'id' => $item->id,
                'version' => (int) $item->version,
                'estado' => (string) $item->estado,
                'fecha' => $item->created_at?->format('d/m/Y H:i') ?: 'Sin fecha',
                'nombre' => (string) $item->nombre_original,
                'tamano' => $item->tamano_legible,
                'paginas' => max((int) ($item->paginas_total ?? 0), 0),
                'actual' => (bool) $item->es_actual,
                'disponible' => $item->archivo_existe,
                'url' => $item->archivo_existe ? route('misrutas.expedientes.preview', $item) : null,
            ])
            ->all();

        if ($limpiarMensaje) {
            $this->mensaje = '';
        }
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

    protected function contexto(): array
    {
        return [
            'tipo_documento_id' => $this->tipoDocumentoId,
            'nivel_id' => $this->nivelId,
            'grado_id' => $this->gradoId,
            'grupo_id' => $this->grupoId,
            'ciclo_escolar_id' => $this->cicloEscolarId,
            'fecha_documento' => now()->toDateString(),
            'folio' => null,
            'origen' => 'subido',
            'tipo_movimiento' => null,
            'motivo' => null,
            'observaciones' => null,
        ];
    }

    protected function consultaDocumentos(): Builder
    {
        return DocumentoAlumno::query()
            ->where('inscripcion_id', $this->inscripcionId)
            ->where('tipo_documento_id', $this->tipoDocumentoId)
            ->where('es_fuente', false)
            ->where(fn (Builder $query) => $this->aplicarContextoDocumento($query));
    }

    protected function aplicarContextoDocumento(Builder $query): Builder
    {
        foreach ([
            'nivel_id' => $this->nivelId,
            'grado_id' => $this->gradoId,
            'grupo_id' => $this->grupoId,
            'ciclo_escolar_id' => $this->cicloEscolarId,
        ] as $campo => $valor) {
            $valor === null ? $query->whereNull($campo) : $query->where($campo, $valor);
        }

        return $query;
    }

    protected function aplicarContextoNoAplica(Builder $query): Builder
    {
        foreach ([
            'nivel_id' => $this->nivelId,
            'grado_id' => $this->gradoId,
            'ciclo_escolar_id' => $this->cicloEscolarId,
        ] as $campo => $valor) {
            $valor === null ? $query->whereNull($campo) : $query->where($campo, $valor);
        }

        return $query;
    }

    protected function asignacionPerteneceAlContexto(array $asignacion): bool
    {
        return (int) ($asignacion['tipo_documento_id'] ?? 0) === $this->tipoDocumentoId
            && $this->mismoNullable($asignacion['nivel_id'] ?? null, $this->nivelId)
            && $this->mismoNullable($asignacion['grado_id'] ?? null, $this->gradoId)
            && $this->mismoNullable($asignacion['grupo_id'] ?? null, $this->grupoId)
            && $this->mismoNullable($asignacion['ciclo_escolar_id'] ?? null, $this->cicloEscolarId);
    }

    protected function mismoNullable(mixed $a, mixed $b): bool
    {
        if ($a === null || $a === '') {
            return $b === null;
        }

        if ($b === null) {
            return false;
        }

        return (int) $a === (int) $b;
    }

    protected function desactivarNoAplica(): void
    {
        DocumentoAlumnoNoAplica::query()
            ->where('inscripcion_id', $this->inscripcionId)
            ->where('tipo_documento_id', $this->tipoDocumentoId)
            ->where(fn (Builder $query) => $this->aplicarContextoNoAplica($query))
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
        abort_if($this->soloLectura || $this->alumno()->expedienteSoloLectura(), 422, 'Este expediente es únicamente histórico.');
    }

    protected function autorizarLectura(): void
    {
        abort_unless(
            auth()->check()
                && (auth()->user()->is_admin || auth()->user()->canAccess('documentos.organizar')),
            403,
            'No tienes permiso para administrar expedientes digitales.'
        );
    }

    protected function alumno(): Inscripcion
    {
        return Inscripcion::withTrashed()->findOrFail($this->inscripcionId);
    }

    public function render()
    {
        $this->autorizarLectura();

        return view('livewire.documentacion.carga-documento-expediente');
    }
}
