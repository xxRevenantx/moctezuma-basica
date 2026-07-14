<?php

namespace App\Livewire\PersonaNivel;

use App\Models\LiberacionSueldo;
use App\Models\cicloEscolar;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\LiberacionSueldoConfiguracion;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\PersonaNivel;
use App\Models\RolePersona;
use App\Services\LiberacionSueldosArchivoService;
use App\Services\LiberacionSueldosService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;
use Livewire\Component;
use Livewire\WithFileUploads;

class LiberacionSueldos extends Component
{
    use WithFileUploads;

    public string $search = '';
    public string $nivelFiltro = '';
    public string $gradoFiltro = '';
    public string $grupoFiltro = '';
    public string $rolFiltro = '';
    public string $historialSearch = '';
    public string $historialNivel = '';
    public string $historialCiclo = '';

    /** @var array<int, int|string> */
    public array $seleccionados = [];

    /** @var array<int|string, array<string, mixed>> */
    public array $firmantes = [];

    public string $fechaDocumento = '';
    public int $quincenaInicio = 13;
    public int $quincenaFin = 14;
    public int $anio = 2026;
    public string $cicloEscolar = '';
    public string $fechaReanudacion = '';
    public ?int $editandoId = null;
    public $logoNuevo = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->fechaDocumento = now()->format('Y-m-d');
        $this->anio = (int) now()->year;
        $ciclo = cicloEscolar::query()->where('es_actual', true)->first() ?: cicloEscolar::query()->latest('id')->first();
        $this->cicloEscolar = (string) ($ciclo?->nombre ?: (now()->year - 1) . '-' . now()->year);
        $reanudacion = now()->copy()->month(8)->day(24);
        if ($reanudacion->isPast()) {
            $reanudacion->addYear();
        }
        $this->fechaReanudacion = $reanudacion->format('Y-m-d');
    }


    public function updatedNivelFiltro(): void
    {
        $this->gradoFiltro = '';
        $this->grupoFiltro = '';
    }

    public function updatedGradoFiltro(): void
    {
        $this->grupoFiltro = '';
    }

    public function updatedSeleccionados(): void
    {
        $this->seleccionados = collect($this->seleccionados)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->sincronizarFirmantes();
    }

    public function updatedFirmantes($value, string $key): void
    {
        if (! str_ends_with($key, '.director_persona_id') || ! $value) {
            return;
        }

        [$nivelId] = explode('.', $key);
        $persona = Persona::query()->find((int) $value);
        if (! $persona) {
            return;
        }

        $service = app(LiberacionSueldosService::class);
        $this->firmantes[$nivelId]['director_nombre'] = $service->nombrePersona($persona);
        $this->firmantes[$nivelId]['director_cargo'] = $service->cargoDireccion($persona, '');
    }

    public function seleccionarVisibles(): void
    {
        $this->seleccionados = $this->queryPersonal()->pluck('persona_nivel.id')->map(fn ($id) => (int) $id)->all();
        $this->sincronizarFirmantes();
    }

    public function limpiarSeleccion(): void
    {
        $this->seleccionados = [];
        $this->firmantes = [];
        $this->editandoId = null;
    }

    public function previsualizar(): void
    {
        $documentos = $this->construirDocumentos();
        $token = (string) Str::uuid();

        session()->put("liberacion_sueldos_preview.{$token}", [
            'usuario_id' => auth()->id(),
            'documentos' => $documentos,
        ]);

        $this->dispatch('abrir-url-liberacion', url: route('misrutas.liberacion-sueldos.preview', $token));
    }

    public function generar(string $formato, string $modo = 'masivo'): void
    {
        abort_unless(in_array($formato, ['pdf', 'word', 'zip'], true), 422);

        $documentos = $this->construirDocumentos();
        $service = app(LiberacionSueldosService::class);
        $ids = [];

        DB::transaction(function () use ($documentos, $service, &$ids) {
            foreach ($documentos as $datos) {
                $ids[] = $service->guardar($datos)->id;
            }
        });

        try {
            $archivos = app(LiberacionSueldosArchivoService::class);
            LiberacionSueldo::query()->whereIn('id', $ids)->get()->each(fn (LiberacionSueldo $item) => $archivos->guardar($item));
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('notificar', tipo: 'warning', mensaje: 'El historial fue guardado, pero uno de los archivos se regenerará al descargarlo.');
        }

        if ($formato === 'pdf' && $modo === 'individual' && count($ids) > 1) {
            $formato = 'zip';
        }

        $url = route('misrutas.liberacion-sueldos.descargar', [
            'formato' => $formato,
            'ids' => implode(',', $ids),
        ]);

        $this->dispatch('abrir-url-liberacion', url: $url);
        $this->dispatch('notificar', tipo: 'success', mensaje: count($ids) . ' liberación(es) registrada(s) en el historial.');
    }

    public function editarHistorial(int $id): void
    {
        $liberacion = LiberacionSueldo::query()->findOrFail($id);
        abort_unless($liberacion->persona_nivel_id, 422, 'La asignación original ya no existe.');

        $this->editandoId = $liberacion->id;
        $this->seleccionados = [(int) $liberacion->persona_nivel_id];
        $this->fechaDocumento = $liberacion->fecha_documento->format('Y-m-d');
        $this->quincenaInicio = $liberacion->quincena_inicio;
        $this->quincenaFin = $liberacion->quincena_fin;
        $this->anio = $liberacion->anio;
        $this->cicloEscolar = (string) ($liberacion->ciclo_escolar ?: $this->cicloEscolar);
        $this->fechaReanudacion = $liberacion->fecha_reanudacion?->format('Y-m-d') ?? '';
        $this->firmantes = [
            (string) $liberacion->nivel_id => [
                'director_persona_id' => '',
                'director_nombre' => $liberacion->director_nombre,
                'director_cargo' => $liberacion->director_cargo,
                'supervisor_nombre' => $liberacion->supervisor_nombre,
                'supervisor_cargo' => $liberacion->supervisor_cargo,
            ],
        ];
        $this->resetValidation();
        $this->dispatch('desplazar-liberacion-formulario');
    }

    public function guardarEdicion(): void
    {
        abort_unless($this->editandoId, 422);
        $documentos = $this->construirDocumentos(true);
        abort_unless(count($documentos) === 1, 422, 'La edición del historial debe realizarse de forma individual.');

        $liberacion = LiberacionSueldo::query()->findOrFail($this->editandoId);
        $archivos = app(LiberacionSueldosArchivoService::class);
        $archivos->eliminar($liberacion);
        $liberacion = app(LiberacionSueldosService::class)->guardar($documentos[0], $liberacion);
        $archivos->guardar($liberacion);

        $this->editandoId = null;
        $this->dispatch('notificar', tipo: 'success', mensaje: 'La liberación fue actualizada correctamente.');
    }

    public function duplicarHistorial(int $id): void
    {
        $original = LiberacionSueldo::query()->findOrFail($id);
        $copia = $original->replicate(['creado_por', 'actualizado_por']);
        $copia->fecha_documento = now()->toDateString();
        $copia->creado_por = auth()->id();
        $copia->actualizado_por = auth()->id();
        $copia->archivo_pdf_path = null;
        $copia->archivo_word_path = null;
        $copia->save();
        app(LiberacionSueldosArchivoService::class)->guardar($copia);

        $this->dispatch('notificar', tipo: 'success', mensaje: 'Se creó una copia editable en el historial.');
    }

    public function eliminarHistorial(int $id): void
    {
        $liberacion = LiberacionSueldo::query()->findOrFail($id);
        app(LiberacionSueldosArchivoService::class)->eliminar($liberacion);
        $liberacion->delete();
        $this->dispatch('notificar', tipo: 'success', mensaje: 'La liberación y sus archivos fueron eliminados del historial.');
    }

    public function guardarLogo(): void
    {
        $this->validate([
            'logoNuevo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
        ], [
            'logoNuevo.required' => 'Selecciona un logotipo.',
            'logoNuevo.image' => 'El archivo debe ser una imagen válida.',
        ]);

        $config = LiberacionSueldoConfiguracion::query()->firstOrNew();
        $config->logo_encabezado_path = $this->logoNuevo->store('liberacion-sueldos', 'public');
        $config->actualizado_por = auth()->id();
        $config->save();
        $this->logoNuevo = null;

        $this->dispatch('notificar', tipo: 'success', mensaje: 'El logotipo del formato fue actualizado.');
    }

    public function restaurarLogo(): void
    {
        $config = LiberacionSueldoConfiguracion::query()->first();
        $config?->delete();
        $this->dispatch('notificar', tipo: 'success', mensaje: 'Se restauró el logotipo oficial predeterminado.');
    }

    private function sincronizarFirmantes(): void
    {
        $service = app(LiberacionSueldosService::class);
        $niveles = PersonaNivel::query()
            ->with(['nivel.director', 'nivel.supervisor'])
            ->whereIn('id', $this->seleccionados)
            ->get()
            ->pluck('nivel')
            ->filter()
            ->unique('id');

        $nuevos = [];
        foreach ($niveles as $nivel) {
            $key = (string) $nivel->id;
            if (isset($this->firmantes[$key])) {
                $nuevos[$key] = $this->firmantes[$key];
                continue;
            }

            $candidatos = $service->directoresPlantilla((int) $nivel->id);
            $seleccionado = $candidatos->count() === 1 ? $candidatos->first() : null;
            $nuevos[$key] = [
                'director_persona_id' => $seleccionado?->id ?: '',
                'director_nombre' => $seleccionado
                    ? $service->nombrePersona($seleccionado)
                    : ($candidatos->count() > 1 ? '' : $service->nombreDirector($nivel->director)),
                'director_cargo' => $seleccionado
                    ? $service->cargoDireccion($seleccionado, (string) $nivel->nombre)
                    : Str::upper((string) ($nivel->director?->cargo ?: 'DIRECTOR')),
                'supervisor_nombre' => $service->nombreDirector($nivel->supervisor),
                'supervisor_cargo' => Str::upper((string) ($nivel->supervisor?->cargo ?: 'SUPERVISOR ESCOLAR')),
            ];
        }

        $this->firmantes = $nuevos;
    }

    /** @return array<int, array<string, mixed>> */
    private function construirDocumentos(bool $permitirInactivo = false): array
    {
        $this->validate([
            'seleccionados' => ['required', 'array', 'min:1'],
            'seleccionados.*' => ['integer', 'exists:persona_nivel,id'],
            'fechaDocumento' => ['required', 'date'],
            'quincenaInicio' => ['required', 'integer', 'between:1,24'],
            'quincenaFin' => ['required', 'integer', 'between:1,24', 'gte:quincenaInicio'],
            'anio' => ['required', 'integer', 'between:2000,2100'],
            'cicloEscolar' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'fechaReanudacion' => ['nullable', 'date'],
            'firmantes.*.director_nombre' => ['required', 'string', 'max:255'],
            'firmantes.*.director_cargo' => ['required', 'string', 'max:120'],
            'firmantes.*.supervisor_nombre' => ['required', 'string', 'max:255'],
            'firmantes.*.supervisor_cargo' => ['required', 'string', 'max:120'],
        ], [
            'seleccionados.required' => 'Selecciona al menos una persona.',
            'quincenaFin.gte' => 'La quincena final debe ser igual o mayor que la inicial.',
        ]);

        $personasNivelQuery = PersonaNivel::query()
            ->with(['persona', 'nivel.director', 'nivel.supervisor'])
            ->whereIn('id', $this->seleccionados);

        if (! $permitirInactivo) {
            $personasNivelQuery->where('estado', PersonaNivel::ESTADO_ACTIVO);
        }

        $personasNivel = $personasNivelQuery->get()
            ->sortBy(fn (PersonaNivel $item) => array_search($item->id, $this->seleccionados, true));

        abort_if($personasNivel->count() !== count($this->seleccionados), 422, 'Una de las personas seleccionadas ya no está activa.');

        $formulario = [
            'fecha_documento' => $this->fechaDocumento,
            'quincena_inicio' => $this->quincenaInicio,
            'quincena_fin' => $this->quincenaFin,
            'anio' => $this->anio,
            'ciclo_escolar' => $this->cicloEscolar,
            'fecha_reanudacion' => $this->fechaReanudacion ?: null,
        ];
        $service = app(LiberacionSueldosService::class);

        return $personasNivel
            ->map(fn (PersonaNivel $item) => $service->construirDatos(
                $item,
                $formulario,
                $this->firmantes[(string) $item->nivel_id] ?? []
            ))
            ->values()
            ->all();
    }

    private function queryPersonal(): Builder
    {
        return app(LiberacionSueldosService::class)
            ->personalActivoQuery()
            ->when($this->nivelFiltro !== '', fn (Builder $query) => $query->where('nivel_id', $this->nivelFiltro))
            ->when($this->gradoFiltro !== '', fn (Builder $query) => $query->whereHas('detalles', fn (Builder $detalle) => $detalle->where('estado', 'activo')->where('grado_id', $this->gradoFiltro)))
            ->when($this->grupoFiltro !== '', fn (Builder $query) => $query->whereHas('detalles', fn (Builder $detalle) => $detalle->where('estado', 'activo')->where('grupo_id', $this->grupoFiltro)))
            ->when($this->rolFiltro !== '', function (Builder $query) {
                $query->whereHas('detalles', fn (Builder $detalle) => $detalle
                    ->where('estado', 'activo')
                    ->whereHas('personaRole', fn (Builder $personaRole) => $personaRole->where('role_persona_id', $this->rolFiltro)));
            })
            ->when(trim($this->search) !== '', function (Builder $query) {
                $buscar = trim($this->search);
                $query->where(function (Builder $sub) use ($buscar) {
                    $sub->whereHas('persona', function (Builder $persona) use ($buscar) {
                        $persona->where('nombre', 'like', "%{$buscar}%")
                            ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                            ->orWhere('apellido_materno', 'like', "%{$buscar}%");
                    })->orWhereHas('detalles.personaRole.rolePersona', fn (Builder $rol) => $rol->where('nombre', 'like', "%{$buscar}%"));
                });
            })
            ->orderBy('nivel_id')
            ->orderBy('orden')
            ->orderBy('id');
    }

    public function render()
    {
        $personal = $this->queryPersonal()->get();
        $niveles = Nivel::query()->orderBy('id')->get();
        $roles = RolePersona::query()->where('status', true)->orderBy('nombre')->get();
        $grados = $this->nivelFiltro !== ''
            ? Grado::query()->where('nivel_id', $this->nivelFiltro)->orderBy('nombre')->get()
            : collect();
        $grupos = $this->gradoFiltro !== ''
            ? Grupo::query()->with('asignacionGrupo')->where('grado_id', $this->gradoFiltro)->get()
            : collect();
        $nivelesSeleccionados = PersonaNivel::query()
            ->with('nivel')
            ->whereIn('id', $this->seleccionados)
            ->get()
            ->pluck('nivel')
            ->filter()
            ->unique('id');

        $directoresPorNivel = $nivelesSeleccionados->mapWithKeys(fn (Nivel $nivel) => [
            $nivel->id => app(LiberacionSueldosService::class)->directoresPlantilla($nivel->id),
        ]);

        $historial = LiberacionSueldo::query()
            ->with(['creador:id,name', 'nivel:id,nombre'])
            ->when($this->historialNivel !== '', fn (Builder $query) => $query->where('nivel_id', $this->historialNivel))
            ->when($this->historialCiclo !== '', fn (Builder $query) => $query->where('ciclo_escolar', $this->historialCiclo))
            ->when(trim($this->historialSearch) !== '', function (Builder $query) {
                $buscar = trim($this->historialSearch);
                $query->where(function (Builder $sub) use ($buscar) {
                    $sub->where('trabajador_nombre', 'like', "%{$buscar}%")
                        ->orWhere('director_nombre', 'like', "%{$buscar}%")
                        ->orWhere('supervisor_nombre', 'like', "%{$buscar}%")
                        ->orWhere('cct', 'like', "%{$buscar}%");
                });
            })
            ->latest()
            ->limit(150)
            ->get();

        $ciclosHistorial = LiberacionSueldo::query()->whereNotNull('ciclo_escolar')->distinct()->orderByDesc('ciclo_escolar')->pluck('ciclo_escolar');
        $config = LiberacionSueldoConfiguracion::query()->first();

        return view('livewire.persona-nivel.liberacion-sueldos', compact(
            'personal', 'niveles', 'grados', 'grupos', 'roles', 'nivelesSeleccionados', 'directoresPorNivel', 'historial', 'ciclosHistorial', 'config'
        ));
    }
}
