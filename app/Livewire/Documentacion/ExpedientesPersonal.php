<?php

namespace App\Livewire\Documentacion;

use App\Models\DocumentoPersonal;
use App\Models\MovimientoPersonal;
use App\Models\Persona;
use App\Models\RolePersona;
use App\Models\TipoDocumentoPersonal;
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

class ExpedientesPersonal extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $buscar = '';
    public ?int $rol_id = null;
    public string $estado_laboral = 'todos';
    public string $estado_expediente = 'todos';
    public int $perPage = 20;

    public ?int $personaSeleccionadaId = null;

    public bool $mostrarCarga = false;
    public bool $reemplazaDocumentoActual = false;
    public ?int $tipo_documento_personal_id = null;
    public ?string $serie_uuid = null;
    public string $subtipo_identificacion = 'ine';
    public string $nombre_estudio = '';
    public string $institucion = '';
    public string $nivel_academico = '';
    public string $numero_cedula = '';
    public string $observaciones = '';
    public $archivo = null;

    public bool $mostrarMovimiento = false;
    public string $tipo_movimiento = 'baja';
    public string $fecha_movimiento = '';
    public string $motivo_movimiento = '';
    public string $observaciones_movimiento = '';

    public array $tiposDocumentos = [];
    public array $roles = [];

    protected $paginationTheme = 'tailwind';

    public function mount(?int $personaId = null): void
    {
        $this->autorizarAdmin();

        $this->tiposDocumentos = TipoDocumentoPersonal::query()
            ->where('activo', true)
            ->orderBy('categoria')
            ->orderBy('orden')
            ->get()
            ->map(fn(TipoDocumentoPersonal $tipo) => [
                'id' => $tipo->id,
                'nombre' => $tipo->nombre,
                'slug' => $tipo->slug,
                'categoria' => $tipo->categoria,
                'descripcion' => $tipo->descripcion,
                'permite_varios' => $tipo->permite_varios,
            ])
            ->values()
            ->all();

        $this->roles = RolePersona::query()
            ->where('status', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'slug'])
            ->map(fn(RolePersona $rol) => [
                'id' => $rol->id,
                'nombre' => $rol->nombre,
                'slug' => $rol->slug,
            ])
            ->values()
            ->all();

        $this->fecha_movimiento = now()->format('Y-m-d');

        if ($personaId && Persona::query()->whereKey($personaId)->exists()) {
            $this->personaSeleccionadaId = $personaId;
        }
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedRolId($value): void
    {
        $this->rol_id = $value ? (int) $value : null;
        $this->resetPage();
    }

    public function updatedEstadoLaboral(): void
    {
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
        $this->rol_id = null;
        $this->estado_laboral = 'todos';
        $this->estado_expediente = 'todos';
        $this->perPage = 20;
        $this->resetPage();
    }

    public function verExpediente(int $personaId): void
    {
        $this->autorizarAdmin();
        abort_unless(Persona::query()->whereKey($personaId)->exists(), 404);

        $this->personaSeleccionadaId = $personaId;
        $this->cerrarCarga();
        $this->cerrarMovimiento();
    }

    public function cerrarExpediente(): void
    {
        $this->personaSeleccionadaId = null;
        $this->cerrarCarga();
        $this->cerrarMovimiento();
    }

    public function abrirCarga(int $tipoId, ?string $serieUuid = null): void
    {
        $this->autorizarAdmin();
        abort_unless($this->personaSeleccionadaId, 422, 'Selecciona una persona.');
        $this->asegurarPersonaModificable();

        $tipo = TipoDocumentoPersonal::query()
            ->where('activo', true)
            ->findOrFail($tipoId);

        $this->resetValidation();
        $this->archivo = null;
        $this->tipo_documento_personal_id = $tipo->id;
        $this->serie_uuid = $serieUuid;
        $this->subtipo_identificacion = 'ine';
        $this->nombre_estudio = '';
        $this->institucion = '';
        $this->nivel_academico = '';
        $this->numero_cedula = '';
        $this->observaciones = '';

        if (!$tipo->permite_varios) {
            $actual = DocumentoPersonal::query()
                ->where('persona_id', $this->personaSeleccionadaId)
                ->where('tipo_documento_personal_id', $tipo->id)
                ->where('es_actual', true)
                ->latest('id')
                ->first();

            if ($actual) {
                $this->serie_uuid = $actual->serie_uuid;
                $this->subtipo_identificacion = $actual->subtipo_identificacion ?: 'ine';
                $this->nombre_estudio = $actual->nombre_estudio ?: '';
                $this->institucion = $actual->institucion ?: '';
                $this->nivel_academico = $actual->nivel_academico ?: '';
                $this->numero_cedula = $actual->numero_cedula ?: '';
            }
        } elseif ($this->serie_uuid) {
            $actual = DocumentoPersonal::query()
                ->where('persona_id', $this->personaSeleccionadaId)
                ->where('tipo_documento_personal_id', $tipo->id)
                ->where('serie_uuid', $this->serie_uuid)
                ->where('es_actual', true)
                ->latest('id')
                ->first();

            abort_unless($actual, 404, 'No se encontró el documento académico seleccionado.');

            $this->subtipo_identificacion = $actual->subtipo_identificacion ?: 'ine';
            $this->nombre_estudio = $actual->nombre_estudio ?: '';
            $this->institucion = $actual->institucion ?: '';
            $this->nivel_academico = $actual->nivel_academico ?: '';
            $this->numero_cedula = $actual->numero_cedula ?: '';
        }

        $this->actualizarIndicadorReemplazo();
        $this->mostrarCarga = true;
    }

    public function cerrarCarga(): void
    {
        $this->mostrarCarga = false;
        $this->reemplazaDocumentoActual = false;
        $this->archivo = null;
        $this->tipo_documento_personal_id = null;
        $this->serie_uuid = null;
        $this->subtipo_identificacion = 'ine';
        $this->nombre_estudio = '';
        $this->institucion = '';
        $this->nivel_academico = '';
        $this->numero_cedula = '';
        $this->observaciones = '';
        $this->resetValidation();
    }

    public function subirDocumento(): void
    {
        $this->autorizarAdmin();
        abort_unless($this->personaSeleccionadaId, 422, 'Selecciona una persona.');
        $this->asegurarPersonaModificable();

        $this->validate([
            'tipo_documento_personal_id' => ['required', 'integer', 'exists:tipos_documentos_personal,id'],
        ]);

        $tipo = TipoDocumentoPersonal::query()
            ->where('activo', true)
            ->findOrFail($this->tipo_documento_personal_id);

        $reglas = [
            'archivo' => [
                'required',
                'file',
                'mimes:pdf',
                'mimetypes:application/pdf,application/x-pdf',
                'max:5120',
            ],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];

        if ($tipo->slug === 'identificacion-oficial') {
            $reglas['subtipo_identificacion'] = ['required', 'in:ine,pasaporte,cedula,otra'];
        }

        if (in_array($tipo->slug, ['titulo-profesional', 'cedula-profesional'], true)) {
            $reglas['nombre_estudio'] = ['nullable', 'string', 'max:255'];
            $reglas['institucion'] = ['nullable', 'string', 'max:255'];
            $reglas['nivel_academico'] = ['nullable', 'in:tecnico,licenciatura,especialidad,maestria,doctorado,otro'];
        }

        if ($tipo->slug === 'cedula-profesional') {
            $reglas['numero_cedula'] = ['nullable', 'string', 'max:100'];
        }

        $this->validate($reglas, [
            'archivo.required' => 'Selecciona un archivo PDF.',
            'archivo.mimes' => 'El documento debe ser un archivo PDF.',
            'archivo.mimetypes' => 'El archivo seleccionado no es un PDF válido.',
            'archivo.max' => 'El PDF no debe superar los 5 MB.',
            'subtipo_identificacion.required' => 'Selecciona el tipo de identificación oficial.',
        ]);

        $rutaGuardada = null;
        $discoExpedientes = config('filesystems.expedientes_disk', 'local');
        $nombreOriginal = Str::limit($this->archivo->getClientOriginalName(), 250, '');
        $tamanoBytes = (int) $this->archivo->getSize();
        $hashSha256 = hash_file('sha256', $this->archivo->getRealPath()) ?: null;
        $serieUuid = $this->serie_uuid ?: (string) Str::uuid();

        try {
            $documentoId = DB::transaction(function () use (
                $tipo,
                $nombreOriginal,
                $tamanoBytes,
                $hashSha256,
                $serieUuid,
                $discoExpedientes,
                &$rutaGuardada
            ): int {
                $consultaSerie = DocumentoPersonal::query()
                    ->where('persona_id', $this->personaSeleccionadaId)
                    ->where('tipo_documento_personal_id', $tipo->id)
                    ->where('serie_uuid', $serieUuid)
                    ->lockForUpdate();

                $version = ((int) (clone $consultaSerie)->max('version')) + 1;

                (clone $consultaSerie)
                    ->where('es_actual', true)
                    ->update([
                        'es_actual' => false,
                        'estado' => 'reemplazado',
                        'updated_at' => now(),
                    ]);

                if (!$tipo->permite_varios) {
                    DocumentoPersonal::query()
                        ->where('persona_id', $this->personaSeleccionadaId)
                        ->where('tipo_documento_personal_id', $tipo->id)
                        ->where('es_actual', true)
                        ->where('serie_uuid', '!=', $serieUuid)
                        ->update([
                            'es_actual' => false,
                            'estado' => 'reemplazado',
                            'updated_at' => now(),
                        ]);
                }

                $directorio = implode('/', [
                    'expedientes-personal',
                    $this->personaSeleccionadaId,
                    $tipo->slug,
                    $serieUuid,
                ]);

                $rutaGuardada = $this->archivo->storeAs(
                    $directorio,
                    Str::uuid() . '.pdf',
                    $discoExpedientes
                );

                if (!$rutaGuardada) {
                    throw new \RuntimeException('No fue posible almacenar el PDF.');
                }

                $documento = DocumentoPersonal::query()->create([
                    'persona_id' => $this->personaSeleccionadaId,
                    'tipo_documento_personal_id' => $tipo->id,
                    'serie_uuid' => $serieUuid,
                    'subtipo_identificacion' => $tipo->slug === 'identificacion-oficial'
                        ? $this->subtipo_identificacion
                        : null,
                    'nombre_estudio' => in_array($tipo->slug, ['titulo-profesional', 'cedula-profesional'], true)
                        ? (trim($this->nombre_estudio) ?: null)
                        : null,
                    'institucion' => in_array($tipo->slug, ['titulo-profesional', 'cedula-profesional'], true)
                        ? (trim($this->institucion) ?: null)
                        : null,
                    'nivel_academico' => in_array($tipo->slug, ['titulo-profesional', 'cedula-profesional'], true)
                        ? ($this->nivel_academico ?: null)
                        : null,
                    'numero_cedula' => $tipo->slug === 'cedula-profesional'
                        ? (trim($this->numero_cedula) ?: null)
                        : null,
                    'disco' => $discoExpedientes,
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

                return $documento->id;
            });

            $tipoGuardadoId = $tipo->id;
            $serieGuardada = $serieUuid;
            $reemplazo = $this->reemplazaDocumentoActual;

            $this->cerrarCarga();

            $this->dispatch(
                'documento-personal-guardado',
                tipoId: $tipoGuardadoId,
                serieUuid: $serieGuardada,
                documentoId: $documentoId,
            );

            $this->dispatch(
                'notify',
                type: 'success',
                message: $reemplazo
                    ? 'Nueva versión guardada. La anterior permanece en el historial.'
                    : 'Documento agregado correctamente al expediente del personal.'
            );
        } catch (Throwable $e) {
            if ($rutaGuardada) {
                Storage::disk($discoExpedientes)->delete($rutaGuardada);
            }

            report($e);
            $this->addError('archivo', 'No fue posible guardar el documento. Inténtalo nuevamente.');
        }
    }

    public function actualizarEstado(int $documentoId, string $estado): void
    {
        $this->autorizarAdmin();
        $this->asegurarPersonaModificable();

        abort_unless(in_array($estado, DocumentoPersonal::ESTADOS, true), 422, 'Estado documental no válido.');

        $documento = DocumentoPersonal::query()
            ->where('persona_id', $this->personaSeleccionadaId)
            ->findOrFail($documentoId);

        abort_unless($documento->es_actual, 422, 'Las versiones históricas no pueden cambiar de estado.');

        $datos = [
            'estado' => $estado,
            'validado_por' => null,
            'validado_at' => null,
        ];

        if ($estado === 'validado') {
            if (! $documento->archivo_existe) {
                $this->dispatch(
                    'notify',
                    type: 'error',
                    message: 'No se puede validar porque el PDF físico no existe en el almacenamiento.'
                );

                return;
            }

            $datos['validado_por'] = auth()->id();
            $datos['validado_at'] = now();
        }

        $documento->update($datos);

        $this->dispatch('notify', type: 'success', message: 'Estado del documento actualizado.');
    }

    public function abrirMovimiento(): void
    {
        $this->autorizarAdmin();
        abort_unless($this->personaSeleccionadaId, 422, 'Selecciona una persona.');

        $persona = Persona::query()->findOrFail($this->personaSeleccionadaId);

        $this->tipo_movimiento = $persona->estado_laboral === 'baja' ? 'reingreso' : 'baja';
        $this->fecha_movimiento = now()->format('Y-m-d');
        $this->motivo_movimiento = '';
        $this->observaciones_movimiento = '';
        $this->resetValidation();
        $this->mostrarMovimiento = true;
    }

    public function cerrarMovimiento(): void
    {
        $this->mostrarMovimiento = false;
        $this->tipo_movimiento = 'baja';
        $this->fecha_movimiento = now()->format('Y-m-d');
        $this->motivo_movimiento = '';
        $this->observaciones_movimiento = '';
        $this->resetValidation();
    }

    public function registrarMovimiento(): void
    {
        $this->autorizarAdmin();
        abort_unless($this->personaSeleccionadaId, 422, 'Selecciona una persona.');

        $this->validate([
            'tipo_movimiento' => ['required', 'in:activo,baja,licencia,suspendido,reingreso'],
            'fecha_movimiento' => ['required', 'date'],
            'motivo_movimiento' => ['nullable', 'string', 'max:2000'],
            'observaciones_movimiento' => ['nullable', 'string', 'max:2000'],
        ], [
            'tipo_movimiento.required' => 'Selecciona el tipo de movimiento.',
            'fecha_movimiento.required' => 'Selecciona la fecha del movimiento.',
        ]);

        DB::transaction(function () {
            $persona = Persona::query()->lockForUpdate()->findOrFail($this->personaSeleccionadaId);

            $persona->update([
                'estado_laboral' => $this->tipo_movimiento,
                'status' => $this->tipo_movimiento !== 'baja',
            ]);

            MovimientoPersonal::query()->create([
                'persona_id' => $persona->id,
                'tipo' => $this->tipo_movimiento,
                'fecha' => $this->fecha_movimiento,
                'motivo' => trim($this->motivo_movimiento) ?: null,
                'observaciones' => trim($this->observaciones_movimiento) ?: null,
                'registrado_por' => auth()->id(),
            ]);
        });

        $this->cerrarMovimiento();
        $this->dispatch('notify', type: 'success', message: 'Movimiento laboral registrado correctamente.');
    }

    public function nombreCompleto(Persona $persona): string
    {
        return trim(implode(' ', array_filter([
            $persona->titulo,
            $persona->nombre,
            $persona->apellido_paterno,
            $persona->apellido_materno,
        ])));
    }

    public function render()
    {
        $this->autorizarAdmin();

        $tiposPersonalesIds = collect($this->tiposDocumentos)
            ->where('categoria', 'personal')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values();

        $totalEsperados = $tiposPersonalesIds->count();

        $queryBase = Persona::query()
            ->with([
                'rolesPersona:id,nombre,slug',
                'documentosPersonal' => fn($query) => $query
                    ->where('es_actual', true)
                    ->whereIn('estado', ['recibido', 'validado'])
                    ->whereIn('tipo_documento_personal_id', $tiposPersonalesIds),
            ])
            ->when($this->buscar !== '', function (Builder $query) {
                $termino = '%' . trim($this->buscar) . '%';

                $query->where(function (Builder $subquery) use ($termino) {
                    $subquery
                        ->where('nombre', 'like', $termino)
                        ->orWhere('apellido_paterno', 'like', $termino)
                        ->orWhere('apellido_materno', 'like', $termino)
                        ->orWhere('curp', 'like', $termino)
                        ->orWhere('rfc', 'like', $termino)
                        ->orWhere('correo', 'like', $termino)
                        ->orWhereRaw(
                            "CONCAT_WS(' ', titulo, nombre, apellido_paterno, apellido_materno) LIKE ?",
                            [$termino]
                        );
                });
            })
            ->when($this->rol_id, fn(Builder $query) => $query->whereHas(
                'rolesPersona',
                fn(Builder $rolQuery) => $rolQuery->whereKey($this->rol_id)
            ))
            ->when(
                $this->estado_laboral !== 'todos',
                fn(Builder $query) => $query->where('estado_laboral', $this->estado_laboral)
            );

        $idsFiltrados = (clone $queryBase)->pluck('personas.id');
        $conteos = $this->conteosDocumentales($idsFiltrados, $tiposPersonalesIds);

        if ($totalEsperados > 0 && $this->estado_expediente !== 'todos') {
            $idsFiltrados = $idsFiltrados
                ->filter(function ($personaId) use ($conteos, $totalEsperados): bool {
                    $completados = (int) ($conteos[$personaId] ?? 0);

                    return $this->estado_expediente === 'completos'
                        ? $completados >= $totalEsperados
                        : $completados < $totalEsperados;
                })
                ->values();

            $idsFiltrados->isEmpty()
                ? $queryBase->whereRaw('1 = 0')
                : $queryBase->whereIn('personas.id', $idsFiltrados);
        }

        $personal = $queryBase
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->paginate($this->perPage);

        $personal->getCollection()->each(function (Persona $persona) use ($conteos, $totalEsperados) {
            $completados = (int) ($conteos[$persona->id] ?? 0);
            $persona->setAttribute('resumen_expediente_personal', [
                'completados' => $completados,
                'total' => $totalEsperados,
                'pendientes' => max($totalEsperados - $completados, 0),
                'porcentaje' => $totalEsperados > 0
                    ? (int) round(($completados / $totalEsperados) * 100)
                    : 100,
                'completo' => $totalEsperados === 0 || $completados >= $totalEsperados,
            ]);
        });

        $completos = $idsFiltrados->filter(fn($id) => (int) ($conteos[$id] ?? 0) >= $totalEsperados)->count();
        $metricas = [
            'total' => $idsFiltrados->count(),
            'completos' => $completos,
            'incompletos' => max($idsFiltrados->count() - $completos, 0),
            'bajas' => Persona::query()->whereIn('id', $idsFiltrados)->where('estado_laboral', 'baja')->count(),
        ];

        $personaSeleccionada = null;
        $documentosSeleccionados = collect();
        $resumenSeleccionado = null;

        if ($this->personaSeleccionadaId) {
            $personaSeleccionada = Persona::query()
                ->with([
                    'rolesPersona:id,nombre,slug',
                    'documentosPersonal.tipoDocumento:id,nombre,slug,categoria,permite_varios,orden',
                    'documentosPersonal.usuarioQueSubio:id,name',
                    'documentosPersonal.usuarioQueValido:id,name',
                    'movimientosLaborales.usuario:id,name',
                ])
                ->find($this->personaSeleccionadaId);

            if ($personaSeleccionada) {
                $documentosSeleccionados = $personaSeleccionada->documentosPersonal
                    ->sortByDesc(fn(DocumentoPersonal $documento) => $documento->created_at?->timestamp ?? 0)
                    ->values();

                $documentosPersonalesVigentes = $documentosSeleccionados
                    ->where('es_actual', true)
                    ->whereIn('estado', ['recibido', 'validado'])
                    ->whereIn('tipo_documento_personal_id', $tiposPersonalesIds);

                $documentosPersonalesConArchivo = $documentosPersonalesVigentes
                    ->filter(fn(DocumentoPersonal $documento) => $documento->archivo_existe);

                $completados = $documentosPersonalesConArchivo
                    ->pluck('tipo_documento_personal_id')
                    ->unique()
                    ->count();

                $archivosFaltantes = $documentosPersonalesVigentes
                    ->reject(fn(DocumentoPersonal $documento) => $documento->archivo_existe)
                    ->count();

                $resumenSeleccionado = [
                    'completados' => $completados,
                    'total' => $totalEsperados,
                    'pendientes' => max($totalEsperados - $completados, 0),
                    'porcentaje' => $totalEsperados > 0
                        ? (int) round(($completados / $totalEsperados) * 100)
                        : 100,
                    'completo' => $totalEsperados === 0 || $completados >= $totalEsperados,
                    'archivos_faltantes' => $archivosFaltantes,
                ];
            } else {
                $this->personaSeleccionadaId = null;
            }
        }

        return view('livewire.documentacion.expedientes-personal', [
            'personal' => $personal,
            'metricas' => $metricas,
            'personaSeleccionada' => $personaSeleccionada,
            'documentosSeleccionados' => $documentosSeleccionados,
            'resumenSeleccionado' => $resumenSeleccionado,
        ]);
    }

    private function conteosDocumentales(Collection $personaIds, Collection $tiposIds): Collection
    {
        if ($personaIds->isEmpty() || $tiposIds->isEmpty()) {
            return collect();
        }

        return DocumentoPersonal::query()
            ->select([
                'id',
                'persona_id',
                'tipo_documento_personal_id',
                'disco',
                'ruta',
                'estado',
                'es_actual',
            ])
            ->whereIn('persona_id', $personaIds)
            ->whereIn('tipo_documento_personal_id', $tiposIds)
            ->where('es_actual', true)
            ->whereIn('estado', ['recibido', 'validado'])
            ->get()
            ->filter(fn(DocumentoPersonal $documento) => $documento->archivo_existe)
            ->groupBy('persona_id')
            ->map(fn(Collection $documentos) => $documentos
                ->pluck('tipo_documento_personal_id')
                ->unique()
                ->count()
            );
    }

    private function actualizarIndicadorReemplazo(): void
    {
        $this->reemplazaDocumentoActual = false;

        if (!$this->personaSeleccionadaId || !$this->tipo_documento_personal_id) {
            return;
        }

        $tipo = TipoDocumentoPersonal::query()->find($this->tipo_documento_personal_id);

        if (!$tipo) {
            return;
        }

        $query = DocumentoPersonal::query()
            ->where('persona_id', $this->personaSeleccionadaId)
            ->where('tipo_documento_personal_id', $tipo->id)
            ->where('es_actual', true);

        if ($tipo->permite_varios) {
            if (!$this->serie_uuid) {
                return;
            }

            $query->where('serie_uuid', $this->serie_uuid);
        }

        $this->reemplazaDocumentoActual = $query->exists();
    }

    private function asegurarPersonaModificable(): void
    {
        $persona = Persona::query()->findOrFail($this->personaSeleccionadaId);

        abort_if(
            $persona->estado_laboral === 'baja' || !$persona->status,
            422,
            'El expediente está en modo histórico porque la persona se encuentra de baja.'
        );
    }

    private function autorizarAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Solo administración puede consultar expedientes del personal.');
    }
}
