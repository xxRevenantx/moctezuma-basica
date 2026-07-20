<?php

namespace App\Livewire\PersonaNivel;

use App\Models\CicloEscolar;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\PersonaNivelHistorial;
use App\Models\ReanudacionCcpPlantilla;
use App\Models\ReanudacionLaboral;
use App\Models\RolePersona;
use App\Services\ReanudacionesArchivoService;
use App\Services\ReanudacionesService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

class Reanudaciones extends Component
{
    public string $search = '';
    /** @var array<int,int|string> */
    public array $nivelesSeleccionados = [];
    public string $gradoFiltro = '';
    public string $grupoFiltro = '';
    public string $rolFiltro = '';

    /** @var array<int,int|string> */
    public array $seleccionados = [];

    public string $cicloEscolarId = '';
    public string $tipoReanudacion = 'receso';
    public string $fechaDirector = '';
    public string $fechaDocente = '';
    public string $copias = '';

    public string $ccpPlantillaId = '';
    public string $ccpNombreNueva = '';

    public string $historialSearch = '';
    public string $historialNivel = '';
    public string $historialCiclo = '';
    public ?string $editandoLote = null;

    public function mount(ReanudacionesService $service): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->nivelesSeleccionados = Nivel::query()
            ->whereIn('slug', ['preescolar', 'primaria', 'secundaria', 'bachillerato'])
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ciclo = CicloEscolar::query()->where('es_actual', true)->first()
            ?: CicloEscolar::query()->latest('inicio_anio')->first();
        $this->cicloEscolarId = (string) ($ciclo?->id ?? '');

        if ($ciclo) {
            $this->fechaDirector = $service->fechaSugerida($ciclo, $this->tipoReanudacion);
            $this->fechaDocente = $this->fechaDirector;
        } else {
            $this->fechaDirector = now()->toDateString();
            $this->fechaDocente = now()->toDateString();
        }

        $plantilla = ReanudacionCcpPlantilla::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->first();
        if ($plantilla) {
            $this->ccpPlantillaId = (string) $plantilla->id;
            $this->copias = (string) $plantilla->contenido;
        }
    }

    public function updatedCicloEscolarId(ReanudacionesService $service): void
    {
        $this->reiniciarDependencias();
        $ciclo = CicloEscolar::query()->find((int) $this->cicloEscolarId);
        if ($ciclo) {
            $this->fechaDirector = $service->fechaSugerida($ciclo, $this->tipoReanudacion);
            $this->fechaDocente = $this->fechaDirector;
        }
    }

    public function updatedTipoReanudacion(ReanudacionesService $service): void
    {
        $this->seleccionados = [];
        $ciclo = CicloEscolar::query()->find((int) $this->cicloEscolarId);
        if ($ciclo) {
            $this->fechaDirector = $service->fechaSugerida($ciclo, $this->tipoReanudacion);
            $this->fechaDocente = $this->fechaDirector;
        }
    }

    public function updatedFechaDocente(): void
    {
        $this->seleccionados = [];
    }

    public function updatedNivelesSeleccionados(): void
    {
        $this->gradoFiltro = '';
        $this->grupoFiltro = '';
        $this->seleccionados = [];
    }

    public function updatedGradoFiltro(): void
    {
        $this->grupoFiltro = '';
        $this->seleccionados = [];
    }

    public function updatedCcpPlantillaId(): void
    {
        $plantilla = ReanudacionCcpPlantilla::query()->find((int) $this->ccpPlantillaId);
        if ($plantilla) {
            $this->copias = (string) $plantilla->contenido;
        }
    }

    public function guardarPlantillaCcp(): void
    {
        $data = $this->validate([
            'ccpNombreNueva' => ['required', 'string', 'max:120'],
            'copias' => ['required', 'string', 'max:4000'],
        ], [
            'ccpNombreNueva.required' => 'Escribe un nombre para la plantilla.',
            'copias.required' => 'Escribe el contenido de C.C.P.',
        ]);

        $plantilla = ReanudacionCcpPlantilla::query()->create([
            'nombre' => trim($data['ccpNombreNueva']),
            'contenido' => trim($data['copias']),
            'activo' => true,
            'orden' => (int) ReanudacionCcpPlantilla::query()->max('orden') + 1,
            'creado_por' => auth()->id(),
            'actualizado_por' => auth()->id(),
        ]);

        $this->ccpPlantillaId = (string) $plantilla->id;
        $this->ccpNombreNueva = '';
        $this->dispatch('notificar', tipo: 'success', mensaje: 'Plantilla de C.C.P. guardada.');
    }

    public function eliminarPlantillaCcp(): void
    {
        $plantilla = ReanudacionCcpPlantilla::query()->find((int) $this->ccpPlantillaId);
        if (! $plantilla) {
            return;
        }

        $plantilla->delete();
        $this->ccpPlantillaId = '';
        $this->copias = '';
        $this->dispatch('notificar', tipo: 'success', mensaje: 'Plantilla de C.C.P. eliminada.');
    }

    public function seleccionarVisibles(array $ids): void
    {
        $this->seleccionados = collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function limpiarSeleccion(): void
    {
        $this->seleccionados = [];
    }

    public function previsualizar(ReanudacionesService $service): void
    {
        // Construir primero valida que todas las asignaciones sigan vigentes.
        $this->documentos($service);
        $token = (string) Str::uuid();

        session()->put("reanudaciones_preview.{$token}", [
            'usuario_id' => auth()->id(),
            'parametros' => [
                'seleccionados' => array_map('intval', $this->seleccionados),
                'ciclo_escolar_id' => (int) $this->cicloEscolarId,
                'tipo' => $this->tipoReanudacion,
                'fecha_director' => $this->fechaDirector,
                'fecha_docente' => $this->fechaDocente,
                'copias' => $this->copias,
            ],
        ]);

        $this->dispatch('abrir-url-reanudaciones', url: route('misrutas.reanudaciones.preview', $token));
    }

    public function generar(string $formato, ReanudacionesService $service, ReanudacionesArchivoService $archivos): void
    {
        abort_unless(in_array($formato, ['pdf', 'zip', 'word'], true), 422);

        if ($formato === 'pdf' && count($this->seleccionados) !== 1) {
            $this->addError('seleccionados', 'El PDF individual requiere seleccionar exactamente una persona. Para varias personas usa ZIP masivo o Word masivo.');
            return;
        }

        $documentos = $this->documentos($service);
        $loteNuevo = (string) Str::uuid();
        $registros = collect();

        DB::transaction(function () use ($documentos, $service, $loteNuevo, $registros) {
            foreach ($documentos as $documento) {
                $registros->push($service->guardarRegistro($documento, $loteNuevo));
            }
        });

        foreach ($registros as $indice => $registro) {
            try {
                $archivos->guardarPdf($registro, $documentos[$indice]);
            } catch (Throwable $e) {
                report($e);
            }
        }

        if ($this->editandoLote) {
            $anteriores = ReanudacionLaboral::query()->where('lote_uuid', $this->editandoLote)->get();
            try {
                $archivos->eliminarLote($anteriores);
            } catch (Throwable $e) {
                report($e);
            }
            $anteriores->each->delete();
        }

        $this->editandoLote = null;

        $url = match ($formato) {
            'pdf' => route('misrutas.reanudaciones.individual', $registros->first()),
            'zip' => route('misrutas.reanudaciones.lote', ['formato' => 'zip', 'lote' => $loteNuevo]),
            'word' => route('misrutas.reanudaciones.lote', ['formato' => 'word', 'lote' => $loteNuevo]),
        };

        $this->dispatch('abrir-url-reanudaciones', url: $url);
        $this->dispatch('notificar', tipo: 'success', mensaje: $registros->count() . ' oficio(s) guardado(s) en el historial.');
        $this->seleccionados = [];
    }

    public function editarLote(string $lote): void
    {
        $registros = ReanudacionLaboral::query()->where('lote_uuid', $lote)->orderBy('id')->get();
        abort_if($registros->isEmpty(), 404);

        $primero = $registros->first();
        $this->editandoLote = $lote;
        $this->cicloEscolarId = (string) $primero->ciclo_escolar_id;
        $this->tipoReanudacion = $primero->tipo_reanudacion;
        $this->fechaDirector = $primero->fecha_director->format('Y-m-d');
        $this->fechaDocente = $primero->fecha_docente->format('Y-m-d');
        $this->copias = (string) ($primero->copias ?? '');
        $this->nivelesSeleccionados = $registros->pluck('nivel_id')->filter()->unique()->map(fn ($id) => (int) $id)->values()->all();
        $this->seleccionados = $registros->pluck('persona_nivel_id')->filter()->unique()->map(fn ($id) => (int) $id)->values()->all();

        $this->dispatch('desplazar-reanudaciones-formulario');
    }

    public function cancelarEdicion(): void
    {
        $this->editandoLote = null;
        $this->seleccionados = [];
    }

    public function eliminarLote(string $lote, ReanudacionesArchivoService $archivos): void
    {
        $registros = ReanudacionLaboral::query()->where('lote_uuid', $lote)->get();
        if ($registros->isEmpty()) {
            return;
        }

        try {
            $archivos->eliminarLote($registros);
        } catch (Throwable $e) {
            report($e);
        }

        DB::transaction(function () use ($registros, $lote) {
            foreach ($registros as $registro) {
                PersonaNivelHistorial::query()->create([
                    'persona_nivel_id' => $registro->persona_nivel_id,
                    'persona_id' => $registro->persona_id,
                    'nivel_id' => $registro->nivel_id,
                    'accion' => 'reanudacion_eliminada',
                    'descripcion' => 'Se eliminó un oficio de reanudación del historial.',
                    'datos_anteriores' => [
                        'reanudacion_laboral_id' => $registro->id,
                        'lote_uuid' => $lote,
                        'tipo' => $registro->tipo_reanudacion,
                        'ciclo_escolar' => $registro->ciclo_nombre,
                    ],
                    'usuario_id' => auth()->id(),
                    'fecha' => now(),
                ]);
                $registro->delete();
            }
        });

        $this->dispatch('notificar', tipo: 'success', mensaje: 'El lote de reanudaciones fue eliminado.');
    }

    /**
     * Restaura el formulario y sus filtros desde localStorage.
     * El lote en edición no se restaura para evitar modificar un historial por accidente.
     *
     * @param array<string, mixed> $estado
     */
    public function restaurarEstadoLocal(array $estado): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $cicloId = filter_var($estado['cicloEscolarId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($cicloId && CicloEscolar::query()->whereKey((int) $cicloId)->exists()) {
            $this->cicloEscolarId = (string) $cicloId;
        }

        $tipo = (string) ($estado['tipoReanudacion'] ?? '');
        if (array_key_exists($tipo, ReanudacionesService::TIPOS)) {
            $this->tipoReanudacion = $tipo;
        }

        $this->fechaDirector = $this->fechaLocal($estado['fechaDirector'] ?? null, $this->fechaDirector);
        $this->fechaDocente = $this->fechaLocal($estado['fechaDocente'] ?? null, $this->fechaDocente);
        $this->search = mb_substr(trim((string) ($estado['search'] ?? '')), 0, 180);
        $this->copias = mb_substr((string) ($estado['copias'] ?? $this->copias), 0, 4000);
        $this->ccpNombreNueva = mb_substr(trim((string) ($estado['ccpNombreNueva'] ?? '')), 0, 120);
        $this->historialSearch = mb_substr(trim((string) ($estado['historialSearch'] ?? '')), 0, 180);

        $nivelesSolicitados = collect(is_array($estado['nivelesSeleccionados'] ?? null) ? $estado['nivelesSeleccionados'] : [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        $this->nivelesSeleccionados = Nivel::query()
            ->whereIn('id', $nivelesSolicitados)
            ->whereIn('slug', ['preescolar', 'primaria', 'secundaria', 'bachillerato'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sortBy(fn (int $id) => $nivelesSolicitados->search($id))
            ->values()
            ->all();

        $gradoId = filter_var($estado['gradoFiltro'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $gradoValido = $gradoId
            && Grado::query()->whereKey((int) $gradoId)
                ->when($this->nivelesSeleccionados !== [], fn ($q) => $q->whereIn('nivel_id', $this->nivelesSeleccionados))
                ->exists();
        $this->gradoFiltro = $gradoValido ? (string) $gradoId : '';

        $grupoId = filter_var($estado['grupoFiltro'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $grupoValido = $grupoId
            && Grupo::query()->whereKey((int) $grupoId)
                ->where('ciclo_escolar_id', (int) $this->cicloEscolarId)
                ->when($this->nivelesSeleccionados !== [], fn ($q) => $q->whereIn('nivel_id', $this->nivelesSeleccionados))
                ->when($this->gradoFiltro !== '', fn ($q) => $q->where('grado_id', (int) $this->gradoFiltro))
                ->where('estado', 'activo')
                ->exists();
        $this->grupoFiltro = $grupoValido ? (string) $grupoId : '';

        $rolId = filter_var($estado['rolFiltro'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $this->rolFiltro = $rolId && RolePersona::query()->whereKey((int) $rolId)->where('status', true)->exists()
            ? (string) $rolId
            : '';

        $plantillaId = filter_var($estado['ccpPlantillaId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $this->ccpPlantillaId = $plantillaId
            && ReanudacionCcpPlantilla::query()->whereKey((int) $plantillaId)->where('activo', true)->exists()
                ? (string) $plantillaId
                : '';

        $historialNivelId = filter_var($estado['historialNivel'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $this->historialNivel = $historialNivelId && Nivel::query()->whereKey((int) $historialNivelId)->exists()
            ? (string) $historialNivelId
            : '';

        $historialCicloId = filter_var($estado['historialCiclo'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $this->historialCiclo = $historialCicloId && CicloEscolar::query()->whereKey((int) $historialCicloId)->exists()
            ? (string) $historialCicloId
            : '';

        $ids = collect(is_array($estado['seleccionados'] ?? null) ? $estado['seleccionados'] : [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        $this->seleccionados = \App\Models\PersonaNivel::query()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sortBy(fn (int $id) => $ids->search($id))
            ->values()
            ->all();

        $this->editandoLote = null;
        $this->resetValidation();
    }

    private function fechaLocal(mixed $valor, string $predeterminada): string
    {
        $fecha = trim((string) $valor);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) && strtotime($fecha) !== false
            ? $fecha
            : $predeterminada;
    }

    /** @return array<int,array<string,mixed>> */
    private function documentos(ReanudacionesService $service): array
    {
        $data = $this->validate([
            'cicloEscolarId' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'tipoReanudacion' => ['required', Rule::in(array_keys(ReanudacionesService::TIPOS))],
            'fechaDirector' => ['required', 'date'],
            'fechaDocente' => ['required', 'date'],
            'copias' => ['nullable', 'string', 'max:4000'],
            'seleccionados' => ['required', 'array', 'min:1'],
            'seleccionados.*' => ['integer', 'exists:persona_nivel,id'],
        ], [
            'seleccionados.required' => 'Selecciona al menos una persona.',
            'seleccionados.min' => 'Selecciona al menos una persona.',
        ]);

        $ciclo = CicloEscolar::query()->findOrFail((int) $data['cicloEscolarId']);

        return $service->construirDocumentos(
            ids: $data['seleccionados'],
            ciclo: $ciclo,
            tipo: $data['tipoReanudacion'],
            fechaDirector: $data['fechaDirector'],
            fechaDocente: $data['fechaDocente'],
            copias: $data['copias'],
        );
    }

    private function reiniciarDependencias(): void
    {
        $this->gradoFiltro = '';
        $this->grupoFiltro = '';
        $this->seleccionados = [];
    }

    public function render(ReanudacionesService $service)
    {
        $ciclos = CicloEscolar::query()->orderByDesc('inicio_anio')->get();
        $niveles = Nivel::query()
            ->whereIn('slug', ['preescolar', 'primaria', 'secundaria', 'bachillerato'])
            ->orderBy('id')
            ->get();
        $roles = RolePersona::query()->where('status', true)->orderBy('nombre')->get();
        $grados = Grado::query()
            ->with('nivel:id,nombre')
            ->when($this->nivelesSeleccionados !== [], fn ($q) => $q->whereIn('nivel_id', $this->nivelesSeleccionados))
            ->orderBy('nivel_id')->orderBy('orden')->get();
        $grupos = Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->when($this->cicloEscolarId !== '', fn ($q) => $q->where('ciclo_escolar_id', (int) $this->cicloEscolarId))
            ->when($this->nivelesSeleccionados !== [], fn ($q) => $q->whereIn('nivel_id', $this->nivelesSeleccionados))
            ->when($this->gradoFiltro !== '', fn ($q) => $q->where('grado_id', (int) $this->gradoFiltro))
            ->where('estado', 'activo')
            ->orderBy('nivel_id')->orderBy('grado_id')->orderBy('asignacion_grupo_id')->get();

        $ciclo = $ciclos->firstWhere('id', (int) $this->cicloEscolarId);
        $resultado = $ciclo
            ? $service->plantilla(
                ciclo: $ciclo,
                fechaReferencia: $this->fechaDocente ?: now()->toDateString(),
                niveles: $this->nivelesSeleccionados,
                gradoId: $this->gradoFiltro !== '' ? (int) $this->gradoFiltro : null,
                grupoId: $this->grupoFiltro !== '' ? (int) $this->grupoFiltro : null,
                rolId: $this->rolFiltro !== '' ? (int) $this->rolFiltro : null,
                busqueda: $this->search,
            )
            : ['listos' => collect(), 'advertencias' => collect(), 'excluidos' => collect()];

        $filas = $resultado['listos']
            ->concat($resultado['advertencias'])
            ->sortBy(fn (array $fila) => [
                (int) ($fila['nivel_id'] ?? PHP_INT_MAX),
                (int) ($fila['orden_plantilla'] ?? PHP_INT_MAX),
                (int) ($fila['id'] ?? PHP_INT_MAX),
            ])
            ->values();

        $historialQuery = ReanudacionLaboral::query()
            ->with(['creador:id,name'])
            ->when($this->historialNivel !== '', fn ($q) => $q->where('nivel_id', (int) $this->historialNivel))
            ->when($this->historialCiclo !== '', fn ($q) => $q->where('ciclo_escolar_id', (int) $this->historialCiclo))
            ->when(trim($this->historialSearch) !== '', function ($q) {
                $termino = '%' . trim($this->historialSearch) . '%';
                $q->where(function ($sub) use ($termino) {
                    $sub->where('persona_nombre', 'like', $termino)
                        ->orWhere('nivel_nombre', 'like', $termino)
                        ->orWhere('ciclo_nombre', 'like', $termino);
                });
            })
            ->latest('created_at')
            ->get();

        $historial = $historialQuery->groupBy('lote_uuid')->map(function (Collection $items, string $lote) {
            $primero = $items->first();
            return [
                'lote' => $lote,
                'tipo' => $primero->tipo_label,
                'tipo_slug' => $primero->tipo_reanudacion,
                'ciclo' => $primero->ciclo_nombre,
                'niveles' => $items->pluck('nivel_nombre')->unique()->values()->implode(', '),
                'cantidad' => $items->count(),
                'fecha' => $primero->created_at,
                'usuario' => $primero->creador?->name ?: 'Sistema',
                'items' => $items->sortBy('id')->values(),
            ];
        })->values()->take(40);

        $ccpPlantillas = ReanudacionCcpPlantilla::query()->where('activo', true)->orderBy('orden')->orderBy('nombre')->get();

        return view('livewire.persona-nivel.reanudaciones', [
            'ciclos' => $ciclos,
            'niveles' => $niveles,
            'roles' => $roles,
            'grados' => $grados,
            'grupos' => $grupos,
            'filas' => $filas,
            'listosCount' => $resultado['listos']->count(),
            'advertenciasCount' => $resultado['advertencias']->count(),
            'excluidos' => $resultado['excluidos'],
            'historial' => $historial,
            'ccpPlantillas' => $ccpPlantillas,
            'tipos' => ReanudacionesService::TIPOS,
        ]);
    }
}
