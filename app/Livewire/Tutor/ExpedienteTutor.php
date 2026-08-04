<?php

namespace App\Livewire\Tutor;

use App\Models\DocumentoTutor;
use App\Models\DocumentoTutorPendienteVincular;
use App\Models\TipoDocumentoTutor;
use App\Models\Tutor;
use App\Services\Expedientes\ExpedienteTutorService;
use App\Services\GestionResponsablesAlumnoService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ExpedienteTutor extends Component
{
    public bool $abierto = false;
    public ?int $tutorId = null;
    public array $tipos = [];

    public function mount(?int $tutorId = null): void
    {
        if (! $tutorId) {
            return;
        }

        $this->abrir($tutorId);
    }

    #[On('abrir-expediente-tutor')]
    public function abrir(int $tutorId): void
    {
        $this->autorizarConsulta();
        abort_unless(Tutor::query()->whereKey($tutorId)->exists(), 404);

        $this->tutorId = $tutorId;
        $this->cargarTipos();
        $this->abierto = true;
    }

    public function cerrar(): void
    {
        $tutorId = $this->tutorId;
        $this->abierto = false;

        if ($tutorId) {
            $this->dispatch('cerrar-expediente-tutor-inline', tutorId: $tutorId);
        }
    }

    public function abrirOrganizador(?int $fuenteId = null): void
    {
        $this->autorizarEdicion();
        $tutor = $this->tutor();

        $this->dispatch(
            'abrir-organizador-tutor',
            tutorId: $tutor->id,
            fuenteId: $fuenteId,
        );
    }

    public function vincularLegado(int $pendienteId, ?int $tutorDestinoId = null): void
    {
        $this->autorizarEdicion();
        $tutor = Tutor::query()->findOrFail($tutorDestinoId ?: $this->tutorId);
        $pendiente = DocumentoTutorPendienteVincular::query()->findOrFail($pendienteId);

        app(ExpedienteTutorService::class)->vincularDocumentoLegado(
            $pendiente,
            $tutor,
            auth()->id(),
        );

        $this->dispatch(
            'notify',
            type: 'success',
            message: 'El documento antiguo quedó vinculado al expediente del responsable.',
        );
        $this->dispatch('expediente-tutor-actualizado', tutorId: $tutor->id);
    }

    public function actualizarConfiguracionTipo(int $tipoId, string $campo, bool $valor): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
        abort_unless(in_array($campo, ['activo', 'es_obligatorio'], true), 422);

        TipoDocumentoTutor::query()
            ->whereKey($tipoId)
            ->update([$campo => $valor, 'updated_at' => now()]);

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
                ->filter(fn (string $item): bool => in_array(
                    $item,
                    GestionResponsablesAlumnoService::PARENTESCOS,
                    true,
                ));

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

    #[On('organizacion-tutor-borrador-actualizado')]
    public function refrescarBorrador(int $tutorId): void
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
            403,
        );
    }

    protected function autorizarEdicion(): void
    {
        abort_unless(
            auth()->user()?->is_admin
                || auth()->user()?->canAccess('documentos.organizar')
                || auth()->user()?->canAccess('alumnos.editar'),
            403,
            'No tienes permiso para modificar expedientes de responsables.',
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
            $this->autorizarConsulta();

            $tutor = Tutor::query()
                ->with([
                    'relacionesActivas.inscripcion' => fn ($query) => $query
                        ->withTrashed()
                        ->select(
                            'inscripciones.id',
                            'nombre',
                            'apellido_paterno',
                            'apellido_materno',
                            'matricula',
                        ),
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
                    })
                    ->values();

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
            'parentescosConfigurables',
        ));
    }
}
