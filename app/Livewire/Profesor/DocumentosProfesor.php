<?php

namespace App\Livewire\Profesor;

use App\Models\CicloEscolar;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\ProfesorDocumentoPlantilla;
use App\Services\ContextoCicloEscolarSesion;
use App\Services\ProfesorDocumentoPortadaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class DocumentosProfesor extends Component
{
    use WithFileUploads;

    public string $submodulo = 'portadas';
    public ?int $nivel_id = null;
    public ?int $ciclo_escolar_id = null;
    public string $modo_descarga = 'individual';
    public string $buscar_persona = '';
    public ?int $persona_individual_id = null;
    public array $personas_seleccionadas = [];
    public ?int $plantilla_id = null;
    public string $nombre_plantilla = '';
    public array $configuracion = [];
    public $portadaNueva = null;

    public function mount(ProfesorDocumentoPortadaService $service): void
    {
        $this->ciclo_escolar_id = app(ContextoCicloEscolarSesion::class)->resolver($this->ciclosEscolares());
        $this->configuracion = $service->defaultConfiguracion();
    }

    public function updatedNivelId(ProfesorDocumentoPortadaService $service): void
    {
        $this->persona_individual_id = null;
        $this->personas_seleccionadas = [];
        $this->buscar_persona = '';
        $this->plantilla_id = $this->plantillaActiva?->id;
        $this->configuracion = $service->configuracionNormalizada($this->plantillaActiva, $this->nivelSeleccionado);
    }

    public function updatedCicloEscolarId(): void
    {
        $this->persona_individual_id = null;
        $this->personas_seleccionadas = [];
        $this->buscar_persona = '';

        if ($this->ciclo_escolar_id) {
            app(ContextoCicloEscolarSesion::class)->recordar($this->ciclo_escolar_id);
        }
    }

    public function updatedPlantillaId(ProfesorDocumentoPortadaService $service): void
    {
        $this->configuracion = $service->configuracionNormalizada($this->plantillaSeleccionada, $this->nivelSeleccionado);
    }

    public function updatedModoDescarga(): void
    {
        $this->persona_individual_id = null;
        $this->personas_seleccionadas = [];
    }

    public function updatedBuscarPersona(): void
    {
        if ($this->modo_descarga === 'individual') {
            $this->persona_individual_id = null;
        }
    }

    #[Computed]
    public function niveles(): Collection
    {
        return Nivel::query()
            ->select('id', 'nombre', 'slug', 'cct')
            ->orderBy('id')
            ->get();
    }

    public function ciclosEscolares(): Collection
    {
        return CicloEscolar::query()
            ->select('id', 'inicio_anio', 'fin_anio', 'es_actual')
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->orderByDesc('fin_anio')
            ->get();
    }

    #[Computed]
    public function ciclos(): Collection
    {
        return $this->ciclosEscolares();
    }

    #[Computed]
    public function nivelSeleccionado(): ?Nivel
    {
        return $this->nivel_id ? Nivel::query()->find($this->nivel_id) : null;
    }

    #[Computed]
    public function cicloSeleccionado(): ?CicloEscolar
    {
        return $this->ciclo_escolar_id ? CicloEscolar::query()->find($this->ciclo_escolar_id) : null;
    }

    #[Computed]
    public function plantillas(): Collection
    {
        if (! $this->nivel_id) {
            return collect();
        }

        return ProfesorDocumentoPlantilla::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('tipo_documento', 'portada')
            ->orderByDesc('activo')
            ->orderByDesc('version')
            ->get();
    }

    #[Computed]
    public function plantillaActiva(): ?ProfesorDocumentoPlantilla
    {
        if (! $this->nivel_id) {
            return null;
        }

        return ProfesorDocumentoPlantilla::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('tipo_documento', 'portada')
            ->where('activo', true)
            ->first();
    }

    #[Computed]
    public function plantillaSeleccionada(): ?ProfesorDocumentoPlantilla
    {
        if ($this->plantilla_id) {
            return ProfesorDocumentoPlantilla::query()
                ->where('nivel_id', $this->nivel_id)
                ->where('tipo_documento', 'portada')
                ->find($this->plantilla_id);
        }

        return $this->plantillaActiva;
    }

    #[Computed]
    public function personas(): Collection
    {
        if (! $this->nivel_id) {
            return collect();
        }

        return $this->consultaPersonalDelNivel(trim($this->buscar_persona))
            ->limit(300)
            ->get();
    }

    private function consultaPersonalDelNivel(string $busqueda = ''): Builder
    {
        return Persona::query()
            ->select('personas.*')
            ->with([
                'personaRoles.rolePersona:id,nombre,slug,status',
                'personaNiveles' => function ($consulta) {
                    $consulta
                        ->where('nivel_id', $this->nivel_id)
                        ->with([
                            'detalles' => function ($detalles) {
                                if ($this->ciclo_escolar_id) {
                                    $detalles->vigenteEnCiclo((int) $this->ciclo_escolar_id, false);
                                }

                                $detalles->with('personaRole.rolePersona:id,nombre,slug,status');
                            },
                        ]);
                },
            ])
            ->where('personas.status', 1)
            ->whereHas('personaNiveles', function ($consulta) {
                $consulta
                    ->where('nivel_id', $this->nivel_id)
                    ->where('estado', 'activo')
                    ->when($this->ciclo_escolar_id, function ($cabecera) {
                        $cabecera->whereHas('detalles', function ($detalles) {
                            $detalles->vigenteEnCiclo((int) $this->ciclo_escolar_id, false);
                        });
                    });
            })
            ->when($busqueda !== '', function ($consulta) use ($busqueda) {
                $consulta->where(function ($q) use ($busqueda) {
                    $q->where('personas.nombre', 'like', '%' . $busqueda . '%')
                        ->orWhere('personas.apellido_paterno', 'like', '%' . $busqueda . '%')
                        ->orWhere('personas.apellido_materno', 'like', '%' . $busqueda . '%')
                        ->orWhere('personas.curp', 'like', '%' . $busqueda . '%')
                        ->orWhere('personas.rfc', 'like', '%' . $busqueda . '%')
                        ->orWhereRaw(
                            "CONCAT_WS(' ', personas.apellido_paterno, personas.apellido_materno, personas.nombre) LIKE ?",
                            ['%' . $busqueda . '%']
                        )
                        ->orWhereRaw(
                            "CONCAT_WS(' ', personas.nombre, personas.apellido_paterno, personas.apellido_materno) LIKE ?",
                            ['%' . $busqueda . '%']
                        );
                });
            })
            ->orderBy('personas.apellido_paterno')
            ->orderBy('personas.apellido_materno')
            ->orderBy('personas.nombre');
    }

    #[Computed]
    public function personasSeleccionadasLista(): Collection
    {
        $ids = collect($this->personas_seleccionadas)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty() || ! $this->nivel_id) {
            return collect();
        }

        $personas = $this->consultaPersonalDelNivel()
            ->whereIn('personas.id', $ids->all())
            ->get();

        return $personas
            ->sortBy(fn ($persona) => $ids->search((int) $persona->id))
            ->values();
    }

    public function seleccionarTodosVisibles(): void
    {
        $idsVisibles = $this->personas->pluck('id')->map(fn ($id) => (int) $id)->all();
        $seleccionados = collect($this->personas_seleccionadas)->map(fn ($id) => (int) $id)->all();
        $this->personas_seleccionadas = array_values(array_unique(array_merge($seleccionados, $idsVisibles)));
    }

    public function quitarTodosVisibles(): void
    {
        $idsVisibles = $this->personas->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->personas_seleccionadas = collect($this->personas_seleccionadas)
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => in_array($id, $idsVisibles, true))
            ->values()
            ->all();
    }

    public function limpiarFiltros(ProfesorDocumentoPortadaService $service): void
    {
        $this->reset([
            'nivel_id',
            'buscar_persona',
            'persona_individual_id',
            'personas_seleccionadas',
            'plantilla_id',
            'nombre_plantilla',
            'portadaNueva',
        ]);

        $this->modo_descarga = 'individual';
        $this->ciclo_escolar_id = app(ContextoCicloEscolarSesion::class)->resolver($this->ciclosEscolares());
        $this->configuracion = $service->defaultConfiguracion();
    }

    public function guardarPlantilla(ProfesorDocumentoPortadaService $service): void
    {
        abort_unless(optional(Auth::user())->is_admin, 403);

        $this->validate([
            'nivel_id' => ['required', 'integer', 'exists:niveles,id'],
            'nombre_plantilla' => ['nullable', 'string', 'max:120'],
            'portadaNueva' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'configuracion.lugar' => ['nullable', 'string', 'max:150'],
            'configuracion.escuela_nombre' => ['nullable', 'string', 'max:200'],
            'configuracion.usar_cargo_real' => ['boolean'],
            'configuracion.escuela_automatica' => ['boolean'],
        ]);

        /** @var TemporaryUploadedFile $archivo */
        $archivo = $this->portadaNueva;
        $nivel = $this->nivelSeleccionado;
        $version = (int) ProfesorDocumentoPlantilla::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('tipo_documento', 'portada')
            ->max('version');
        $version++;

        $directorio = 'profesores/portadas/' . Str::slug($nivel?->slug ?: (string) $this->nivel_id);
        $path = $archivo->store($directorio, 'public');
        $nombre = trim($this->nombre_plantilla) !== ''
            ? trim($this->nombre_plantilla)
            : 'Portada institucional ' . ($nivel?->nombre ?: 'Nivel') . ' v' . $version;

        DB::transaction(function () use ($service, $path, $archivo, $version, $nombre) {
            ProfesorDocumentoPlantilla::query()
                ->where('nivel_id', $this->nivel_id)
                ->where('tipo_documento', 'portada')
                ->update(['activo' => false, 'actualizado_por' => Auth::id()]);

            $plantilla = ProfesorDocumentoPlantilla::query()->create([
                'nivel_id' => $this->nivel_id,
                'tipo_documento' => 'portada',
                'nombre' => $nombre,
                'version' => $version,
                'archivo_path' => $path,
                'nombre_original' => $archivo->getClientOriginalName(),
                'mime_type' => $archivo->getMimeType(),
                'size_bytes' => $archivo->getSize(),
                'activo' => true,
                'configuracion' => $service->configuracionNormalizada($this->configuracion, $this->nivelSeleccionado),
                'creado_por' => Auth::id(),
                'actualizado_por' => Auth::id(),
            ]);

            $this->plantilla_id = (int) $plantilla->id;
        });

        $this->reset(['portadaNueva', 'nombre_plantilla']);
        $this->dispatch('notificar', tipo: 'success', mensaje: 'La portada se guardó correctamente y quedó activa para el nivel seleccionado.');
    }

    public function activarPlantilla(int $plantillaId): void
    {
        abort_unless(optional(Auth::user())->is_admin, 403);

        $plantilla = ProfesorDocumentoPlantilla::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('tipo_documento', 'portada')
            ->findOrFail($plantillaId);

        DB::transaction(function () use ($plantilla) {
            ProfesorDocumentoPlantilla::query()
                ->where('nivel_id', $plantilla->nivel_id)
                ->where('tipo_documento', $plantilla->tipo_documento)
                ->update(['activo' => false, 'actualizado_por' => Auth::id()]);

            $plantilla->update([
                'activo' => true,
                'actualizado_por' => Auth::id(),
            ]);
        });

        $this->plantilla_id = (int) $plantilla->id;
        $this->dispatch('notificar', tipo: 'success', mensaje: 'La plantilla seleccionada ahora es la portada activa.');
    }

    public function desactivarPlantilla(int $plantillaId): void
    {
        abort_unless(optional(Auth::user())->is_admin, 403);

        $plantilla = ProfesorDocumentoPlantilla::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('tipo_documento', 'portada')
            ->findOrFail($plantillaId);

        $plantilla->update([
            'activo' => false,
            'actualizado_por' => Auth::id(),
        ]);

        if ((int) $this->plantilla_id === $plantilla->id) {
            $this->plantilla_id = null;
        }

        $this->dispatch('notificar', tipo: 'success', mensaje: 'La plantilla se desactivó. Su versión histórica se conserva.');
    }

    #[Computed]
    public function personaPreview(): ?Persona
    {
        if ($this->modo_descarga === 'individual' && $this->persona_individual_id) {
            return $this->consultaPersonalDelNivel()->find($this->persona_individual_id);
        }

        if ($this->modo_descarga === 'seleccionados') {
            return $this->personasSeleccionadasLista->first();
        }

        return null;
    }

    #[Computed]
    public function datosPreview(): ?array
    {
        if (! $this->personaPreview || ! $this->nivelSeleccionado) {
            return null;
        }

        $plantillaContexto = $this->plantillaSeleccionada ?: ['configuracion' => $this->configuracion];

        return app(ProfesorDocumentoPortadaService::class)->datosPortada(
            $this->personaPreview,
            $this->nivelSeleccionado,
            $this->cicloSeleccionado,
            $plantillaContexto,
        );
    }

    #[Computed]
    public function fondoPreviewUrl(): ?string
    {
        if ($this->portadaNueva instanceof TemporaryUploadedFile) {
            return $this->portadaNueva->temporaryUrl();
        }

        return app(ProfesorDocumentoPortadaService::class)->fondoUrl($this->plantillaSeleccionada);
    }

    #[Computed]
    public function puedeDescargar(): bool
    {
        if (! $this->nivel_id || ! $this->plantillaSeleccionada) {
            return false;
        }

        return $this->modo_descarga === 'individual'
            ? filled($this->persona_individual_id)
            : count($this->personas_seleccionadas) > 0;
    }

    #[Computed]
    public function parametrosDescarga(): array
    {
        return [
            'tipo' => 'portada',
            'nivel_id' => $this->nivel_id,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'modo_descarga' => $this->modo_descarga,
            'persona_individual_id' => $this->persona_individual_id,
            'personas' => implode(',', collect($this->personas_seleccionadas)->map(fn ($id) => (int) $id)->filter()->all()),
            'plantilla_id' => $this->plantillaSeleccionada?->id,
        ];
    }

    #[Computed]
    public function urlDescargaPdf(): ?string
    {
        return $this->puedeDescargar
            ? route('profesores.documentos.portadas.pdf', $this->parametrosDescarga)
            : null;
    }

    public function nombrePersona($persona): string
    {
        return trim(
            ($persona->titulo ? $persona->titulo . ' ' : '') .
            ($persona->nombre ?? '') . ' ' .
            ($persona->apellido_paterno ?? '') . ' ' .
            ($persona->apellido_materno ?? '')
        );
    }

    public function rolPrincipal($persona): string
    {
        $personaNivel = $persona->personaNiveles->firstWhere('nivel_id', (int) $this->nivel_id);

        return $personaNivel?->detalles
            ?->map(fn ($detalle) => $detalle->personaRole?->rolePersona?->nombre)
            ->filter()
            ->first() ?: 'Personal asignado';
    }

    public function render()
    {
        return view('livewire.profesor.documentos-profesor');
    }
}
