<?php

namespace App\Livewire\Accion\Generales;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Services\GestionAcademicaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class PromocionAlumnos extends Component
{
    use WithPagination;

    public string $slug_nivel = '';
    public ?Nivel $nivel = null;
    public Collection $ciclosEscolares;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $semestresOrigen;
    public Collection $gruposOrigen;
    public Collection $semestresDestino;
    public Collection $gruposDestino;

    public ?int $ciclo_origen_id = null;
    public ?int $ciclo_destino_id = null;
    public ?int $generacion_id = null;
    public ?int $grado_origen_id = null;
    public ?int $semestre_origen_id = null;
    public ?int $grupo_origen_id = null;
    public ?int $grado_destino_id = null;
    public ?int $semestre_destino_id = null;
    public ?int $grupo_destino_id = null;
    public string $motivo = '';
    public array $seleccionados = [];
    public bool $seleccionarPagina = false;
    public bool $mostrarVistaPrevia = false;
    public array $vistaPrevia = [];
    public string $hashVistaPrevia = '';
    public string $confirmacion = '';
    public string $fecha_efectiva = '';
    public string $tipoResultado = 'promovido';

    protected $paginationTheme = 'tailwind';

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();
        $this->ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual']);
        $this->ciclo_origen_id = $this->ciclosEscolares->firstWhere('es_actual', true)?->id
            ?? $this->ciclosEscolares->first()?->id;
        $this->ciclo_destino_id = $this->resolverSiguienteCicloId($this->ciclo_origen_id) ?? $this->ciclo_origen_id;
        $this->generaciones = $this->cargarGeneraciones();
        $this->grados = Grado::query()->where('nivel_id', $this->nivel->id)->orderBy('orden')->get();
        $this->semestresOrigen = collect();
        $this->gruposOrigen = collect();
        $this->semestresDestino = collect();
        $this->gruposDestino = collect();
        $this->fecha_efectiva = now()->toDateString();
    }

    private function cargarGeneraciones(): Collection
    {
        return Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->when($this->ciclo_origen_id, fn (Builder $query) => $query->whereHas(
                'grupos',
                fn (Builder $grupos) => $grupos
                    ->where('ciclo_escolar_id', $this->ciclo_origen_id)
                    ->where('estado', 'activo')
            ))
            ->orderByDesc('status')
            ->orderByDesc('anio_ingreso')
            ->get();
    }

    private function resolverSiguienteCicloId(?int $cicloId): ?int
    {
        $origen = $this->ciclosEscolares->firstWhere('id', $cicloId);

        if (! $origen) {
            return null;
        }

        return $this->ciclosEscolares
            ->first(fn ($ciclo) => (int) $ciclo->inicio_anio === (int) $origen->inicio_anio + 1)?->id;
    }

    public function esBachillerato(): bool
    {
        return str_contains(mb_strtolower(($this->nivel?->slug ?? '') . ' ' . ($this->nivel?->nombre ?? '')), 'bachillerato');
    }

    public function updatedCicloOrigenId(): void
    {
        $this->ciclo_origen_id = $this->ciclo_origen_id ? (int) $this->ciclo_origen_id : null;
        $this->ciclo_destino_id = $this->resolverSiguienteCicloId($this->ciclo_origen_id) ?? $this->ciclo_origen_id;
        $this->generaciones = $this->cargarGeneraciones();
        $this->limpiarContexto();
    }

    public function updatedCicloDestinoId(): void
    {
        $this->grupo_destino_id = null;
        $this->prepararDestinoAutomatico();
    }

    public function updatedGeneracionId(): void
    {
        $this->limpiarContexto(false);
    }

    public function updatedGradoOrigenId(): void
    {
        $this->semestre_origen_id = null;
        $this->grupo_origen_id = null;
        $this->semestresOrigen = $this->grado_origen_id
            ? Semestre::query()->where('grado_id', $this->grado_origen_id)->orderBy('orden_global')->orderBy('numero')->get()
            : collect();
        $this->gruposOrigen = $this->esBachillerato() ? collect() : $this->cargarGrupos($this->grado_origen_id, null, $this->ciclo_origen_id);
        $this->prepararDestinoAutomatico();
        $this->seleccionados = [];
        $this->resetPage();
    }

    public function updatedSemestreOrigenId(): void
    {
        $this->grupo_origen_id = null;
        $this->gruposOrigen = $this->cargarGrupos($this->grado_origen_id, $this->semestre_origen_id, $this->ciclo_origen_id);
        $this->prepararDestinoAutomatico();
        $this->seleccionados = [];
        $this->resetPage();
    }

    public function updatedGrupoOrigenId(): void
    {
        $this->seleccionados = [];
        $this->seleccionarPagina = false;
        $this->resetPage();
    }

    public function updatedGradoDestinoId(): void
    {
        $this->semestre_destino_id = null;
        $this->grupo_destino_id = null;
        $this->semestresDestino = $this->grado_destino_id
            ? Semestre::query()->where('grado_id', $this->grado_destino_id)->orderBy('orden_global')->orderBy('numero')->get()
            : collect();
        $this->gruposDestino = $this->esBachillerato() ? collect() : $this->cargarGrupos($this->grado_destino_id, null, $this->ciclo_destino_id);
    }

    public function updatedSemestreDestinoId(): void
    {
        $this->grupo_destino_id = null;
        $this->gruposDestino = $this->cargarGrupos($this->grado_destino_id, $this->semestre_destino_id, $this->ciclo_destino_id);
    }

    public function updatedSeleccionarPagina(bool $valor): void
    {
        $this->seleccionados = $valor
            ? $this->alumnosQuery()->limit(25)->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    public function promoverSeleccionados(): void
    {
        $this->prepararPromocion();
    }

    public function prepararPromocion(): void
    {
        $this->tipoResultado = 'promovido';
        $this->validarPromocion();
        $this->vistaPrevia = $this->construirVistaPrevia();
        $this->hashVistaPrevia = $this->calcularHashVistaPrevia($this->vistaPrevia);
        $this->confirmacion = '';
        $this->mostrarVistaPrevia = true;
    }

    public function confirmarPromocion(GestionAcademicaService $service): void
    {
        $this->validarPromocion();
        $this->validate([
            'fecha_efectiva' => ['required', 'date'],
            'confirmacion' => ['required', 'in:'.($this->tipoResultado === 'no_promovido' ? 'NO PROMOVER' : 'PROMOVER')],
        ]);

        $vistaActual = $this->construirVistaPrevia();
        if (! hash_equals($this->hashVistaPrevia, $this->calcularHashVistaPrevia($vistaActual))) {
            $this->addError('confirmacion', 'La información cambió después de generar la vista previa. Revísala nuevamente.');
            $this->vistaPrevia = $vistaActual;
            $this->hashVistaPrevia = $this->calcularHashVistaPrevia($vistaActual);
            return;
        }

        $alumnos = $this->alumnosQuery()
            ->whereIn('id', array_map('intval', $this->seleccionados))
            ->lockForUpdate()
            ->get();

        foreach ($alumnos as $alumno) {
            $destino = [
                'ciclo_escolar_id' => $this->ciclo_destino_id,
                'nivel_id' => $this->nivel->id,
                'generacion_id' => $this->generacion_id,
                'grado_id' => $this->grado_destino_id,
                'semestre_id' => $this->esBachillerato() ? $this->semestre_destino_id : null,
                'grupo_id' => $this->grupo_destino_id,
                'matricula' => $alumno->matricula,
            ];

            if ($this->tipoResultado === 'no_promovido') {
                $service->continuarNoPromovido($alumno, $destino, trim($this->motivo), auth()->id(), $this->fecha_efectiva);
            } else {
                $service->promoverAlumno($alumno, $destino, trim($this->motivo), auth()->id(), $this->fecha_efectiva);
            }
        }

        $total = $alumnos->count();
        $this->seleccionados = [];
        $this->seleccionarPagina = false;
        $this->motivo = '';
        $this->confirmacion = '';
        $this->vistaPrevia = [];
        $this->hashVistaPrevia = '';
        $this->mostrarVistaPrevia = false;
        $this->resetPage();
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => $this->tipoResultado === 'no_promovido' ? 'Continuidad aplicada' : 'Promoción aplicada',
            'text' => $this->tipoResultado === 'no_promovido'
                ? "{$total} alumno(s) continuarán en el mismo grado o semestre. El ciclo anterior quedó conservado como evidencia."
                : "Se promovieron {$total} alumno(s). El ciclo anterior quedó conservado como evidencia y se creó el registro del ciclo destino.",
            'position' => 'top-end',
        ]);
    }

    public function cancelarVistaPrevia(): void
    {
        $this->mostrarVistaPrevia = false;
        $this->vistaPrevia = [];
        $this->hashVistaPrevia = '';
        $this->confirmacion = '';
    }

    private function validarPromocion(): void
    {
        $reglas = [
            'ciclo_origen_id' => ['required', 'exists:ciclo_escolares,id'],
            'ciclo_destino_id' => ['required', 'exists:ciclo_escolares,id'],
            'generacion_id' => ['required', 'exists:generaciones,id'],
            'grado_origen_id' => ['required', 'exists:grados,id'],
            'grupo_origen_id' => ['required', 'exists:grupos,id'],
            'grado_destino_id' => ['required', 'exists:grados,id'],
            'grupo_destino_id' => ['required', 'exists:grupos,id'],
            'seleccionados' => ['required', 'array', 'min:1'],
            'seleccionados.*' => ['integer', 'exists:inscripciones,id'],
            'motivo' => ['required', 'string', 'min:5', 'max:1000'],
        ];
        if ($this->esBachillerato()) {
            $reglas['semestre_origen_id'] = ['required', 'exists:semestres,id'];
            $reglas['semestre_destino_id'] = ['required', 'exists:semestres,id'];
        }
        $this->validate($reglas);
    }

    private function construirVistaPrevia(): array
    {
        $grupoDestino = Grupo::query()->with(['asignacionGrupo', 'grado', 'semestre', 'generacion', 'cicloEscolar'])->findOrFail($this->grupo_destino_id);

        return $this->alumnosQuery()
            ->whereIn('id', array_map('intval', $this->seleccionados))
            ->get()
            ->map(fn (Inscripcion $alumno): array => [
                'id' => $alumno->id,
                'alumno' => trim($alumno->apellido_paterno . ' ' . $alumno->apellido_materno . ' ' . $alumno->nombre),
                'matricula' => $alumno->matricula,
                'origen' => [
                    'ciclo_id' => (int) $alumno->ciclo_escolar_id,
                    'grado_id' => (int) $alumno->grado_id,
                    'semestre_id' => (int) ($alumno->semestre_id ?? 0),
                    'grupo_id' => (int) $alumno->grupo_id,
                    'texto' => trim(($alumno->grado?->nombre ?? '—') . ($alumno->semestre ? ' · Sem. ' . $alumno->semestre->numero : '') . ' · ' . ($alumno->grupo?->asignacionGrupo?->nombre ?? '—')),
                ],
                'destino' => [
                    'ciclo_id' => (int) $this->ciclo_destino_id,
                    'grado_id' => (int) $grupoDestino->grado_id,
                    'semestre_id' => (int) ($grupoDestino->semestre_id ?? 0),
                    'grupo_id' => (int) $grupoDestino->id,
                    'texto' => trim(($grupoDestino->grado?->nombre ?? '—') . ($grupoDestino->semestre ? ' · Sem. ' . $grupoDestino->semestre->numero : '') . ' · ' . ($grupoDestino->asignacionGrupo?->nombre ?? '—')),
                ],
            ])
            ->values()
            ->all();
    }

    private function calcularHashVistaPrevia(array $vista): string
    {
        return hash('sha256', json_encode([
            'ciclo_origen_id' => $this->ciclo_origen_id,
            'ciclo_destino_id' => $this->ciclo_destino_id,
            'generacion_id' => $this->generacion_id,
            'grupo_destino_id' => $this->grupo_destino_id,
            'fecha_efectiva' => $this->fecha_efectiva,
            'tipo_resultado' => $this->tipoResultado,
            'filas' => $vista,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function marcarNoPromovidos(): void
    {
        $this->validate([
            'ciclo_origen_id' => ['required', 'exists:ciclo_escolares,id'],
            'ciclo_destino_id' => ['required', 'different:ciclo_origen_id', 'exists:ciclo_escolares,id'],
            'generacion_id' => ['required', 'exists:generaciones,id'],
            'grado_origen_id' => ['required', 'exists:grados,id'],
            'grupo_origen_id' => ['required', 'exists:grupos,id'],
            'seleccionados' => ['required', 'array', 'min:1'],
            'seleccionados.*' => ['integer', 'exists:inscripciones,id'],
            'motivo' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $this->tipoResultado = 'no_promovido';
        $this->grado_destino_id = $this->grado_origen_id;
        $this->semestre_destino_id = $this->esBachillerato() ? $this->semestre_origen_id : null;
        $this->gruposDestino = $this->cargarGrupos($this->grado_destino_id, $this->semestre_destino_id, $this->ciclo_destino_id);

        $grupoOrigen = Grupo::query()->find($this->grupo_origen_id);
        $coincidente = $this->gruposDestino->first(fn (Grupo $grupo) => (int) $grupo->asignacion_grupo_id === (int) $grupoOrigen?->asignacion_grupo_id);
        $this->grupo_destino_id = $coincidente?->id ?? $this->gruposDestino->first()?->id;

        if (! $this->grupo_destino_id) {
            $this->addError('grupo_destino_id', 'No existe un grupo del mismo grado o semestre en el ciclo destino. Créalo antes de continuar.');
            return;
        }

        $this->validarPromocion();
        $this->vistaPrevia = $this->construirVistaPrevia();
        $this->hashVistaPrevia = $this->calcularHashVistaPrevia($this->vistaPrevia);
        $this->confirmacion = '';
        $this->mostrarVistaPrevia = true;
    }

    private function prepararDestinoAutomatico(): void
    {
        $this->grado_destino_id = null;
        $this->semestre_destino_id = null;
        $this->grupo_destino_id = null;
        $this->semestresDestino = collect();
        $this->gruposDestino = collect();

        if ($this->esBachillerato() && $this->semestre_origen_id) {
            $origen = Semestre::query()->find($this->semestre_origen_id);
            $siguiente = Semestre::query()
                ->where('numero', '>', (int) ($origen?->numero ?? 0))
                ->whereHas('grado', fn (Builder $q) => $q->where('nivel_id', $this->nivel->id))
                ->orderBy('numero')
                ->first();
            if ($siguiente) {
                $this->ciclo_destino_id = (int) $siguiente->numero % 2 === 0
                    ? $this->ciclo_origen_id
                    : ($this->resolverSiguienteCicloId($this->ciclo_origen_id) ?? $this->ciclo_destino_id);
                $this->grado_destino_id = $siguiente->grado_id;
                $this->semestre_destino_id = $siguiente->id;
                $this->semestresDestino = Semestre::query()->where('grado_id', $siguiente->grado_id)->orderBy('orden_global')->get();
                $this->gruposDestino = $this->cargarGrupos($siguiente->grado_id, $siguiente->id, $this->ciclo_destino_id);
            }
            return;
        }

        if ($this->grado_origen_id) {
            $origen = $this->grados->firstWhere('id', $this->grado_origen_id);
            $siguiente = $this->grados->first(fn ($g) => (int) $g->orden > (int) ($origen?->orden ?? 0));
            if ($siguiente) {
                $this->ciclo_destino_id = $this->resolverSiguienteCicloId($this->ciclo_origen_id)
                    ?? $this->ciclo_destino_id;
                $this->grado_destino_id = $siguiente->id;
                $this->gruposDestino = $this->cargarGrupos($siguiente->id, null, $this->ciclo_destino_id);
            }
        }
    }

    private function cargarGrupos(?int $gradoId, ?int $semestreId, ?int $cicloEscolarId): Collection
    {
        if (! $this->generacion_id || ! $gradoId) {
            return collect();
        }
        return Grupo::query()
            ->with('asignacionGrupo')
            ->withCount(['inscripciones as alumnos_activos_count' => fn (Builder $alumnos) => $alumnos
                ->where('activo', true)
                ->whereNull('deleted_at')])
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('estado', 'activo')
            ->where('nivel_id', $this->nivel->id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $gradoId)
            ->when($this->esBachillerato(), fn (Builder $q) => $q->where('semestre_id', $semestreId), fn (Builder $q) => $q->whereNull('semestre_id'))
            ->get()->sortBy(fn ($g) => $g->asignacionGrupo?->nombre ?? $g->id)->values();
    }

    private function alumnosQuery(): Builder
    {
        return Inscripcion::query()
            ->with(['grado', 'semestre', 'grupo.asignacionGrupo'])
            ->where('nivel_id', $this->nivel->id)
            ->when($this->ciclo_origen_id, fn (Builder $q) => $q->where('ciclo_escolar_id', $this->ciclo_origen_id))
            ->when($this->generacion_id, fn (Builder $q) => $q->where('generacion_id', $this->generacion_id))
            ->when($this->grado_origen_id, fn (Builder $q) => $q->where('grado_id', $this->grado_origen_id))
            ->when($this->esBachillerato() && $this->semestre_origen_id, fn (Builder $q) => $q->where('semestre_id', $this->semestre_origen_id))
            ->when($this->grupo_origen_id, fn (Builder $q) => $q->where('grupo_id', $this->grupo_origen_id))
            ->where('activo', true)
            ->whereIn('estatus', ['activo', 'reingreso', 'no_promovido'])
            ->orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombre');
    }

    private function limpiarContexto(bool $limpiarGeneracion = true): void
    {
        if ($limpiarGeneracion) $this->generacion_id = null;
        $this->grado_origen_id = null;
        $this->semestre_origen_id = null;
        $this->grupo_origen_id = null;
        $this->grado_destino_id = null;
        $this->semestre_destino_id = null;
        $this->grupo_destino_id = null;
        $this->semestresOrigen = collect();
        $this->gruposOrigen = collect();
        $this->semestresDestino = collect();
        $this->gruposDestino = collect();
        $this->seleccionados = [];
        $this->seleccionarPagina = false;
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.accion.generales.promocion-alumnos', [
            'alumnos' => $this->alumnosQuery()->paginate(25),
        ]);
    }
}
