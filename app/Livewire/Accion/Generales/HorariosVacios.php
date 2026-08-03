<?php

namespace App\Livewire\Accion\Generales;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Services\ContextoEscolarService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class HorariosVacios extends Component
{
    public string $slug_nivel = '';

    public ?Nivel $nivel = null;
    public bool $esBachillerato = false;

    public Collection $ciclosEscolares;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $semestres;
    public Collection $horas;

    public ?int $ciclo_escolar_id = null;
    public string $alcance = 'nivel';
    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;
    public array $grupos_seleccionados = [];

    public ?int $hora_inicio_id = null;
    public ?int $hora_fin_id = null;
    public string $estilo_celda = 'lineas';

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;

        $this->nivel = Nivel::query()
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $this->esBachillerato = (int) $this->nivel->id === 4
            || $this->nivel->slug === 'bachillerato';

        $this->ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->orderByDesc('id')
            ->get(['id', 'inicio_anio', 'fin_anio']);

        $this->generaciones = collect();
        $this->grados = collect();
        $this->semestres = collect();

        $this->horas = Hora::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('hora_inicio')
            ->get(['id', 'nivel_id', 'hora_inicio', 'hora_fin', 'orden']);

        $this->ciclo_escolar_id = $this->ciclosEscolares->first()?->id;
        $this->hora_inicio_id = $this->horas->first()?->id;
        $this->hora_fin_id = $this->horas->last()?->id;

        $this->cargarGeneraciones();
    }

    public function updatedAlcance(): void
    {
        $this->grupos_seleccionados = [];

        if ($this->alcance === 'nivel') {
            $this->generacion_id = null;
            $this->grado_id = null;
            $this->semestre_id = null;
            $this->semestres = collect();
        }
    }

    public function updatedCicloEscolarId(): void
    {
        $this->generacion_id = null;
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->generaciones = collect();
        $this->grados = collect();
        $this->semestres = collect();
        $this->grupos_seleccionados = [];
        $this->cargarGeneraciones();
    }

    public function updatedGeneracionId(): void
    {
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grados = collect();
        $this->semestres = collect();
        $this->grupos_seleccionados = [];
        $this->cargarGrados();
    }

    public function updatedGradoId(): void
    {
        $this->semestre_id = null;
        $this->grupos_seleccionados = [];
        $this->cargarSemestres();
    }

    public function updatedSemestreId(): void
    {
        $this->grupos_seleccionados = [];
    }

    public function seleccionarTodosLosGrupos(): void
    {
        $this->grupos_seleccionados = $this->gruposDisponibles
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();
    }

    public function limpiarSeleccionGrupos(): void
    {
        $this->grupos_seleccionados = [];
    }

    private function cargarGeneraciones(): void
    {
        if (!$this->ciclo_escolar_id || !$this->nivel) {
            $this->generaciones = collect();
            return;
        }

        $this->generaciones = app(ContextoEscolarService::class)->generaciones(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
        );
    }

    private function cargarGrados(): void
    {
        if (!$this->ciclo_escolar_id || !$this->generacion_id || !$this->nivel) {
            $this->grados = collect();
            return;
        }

        $this->grados = app(ContextoEscolarService::class)->grados(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: $this->generacion_id,
        );
    }

    private function cargarSemestres(): void
    {
        if (!$this->esBachillerato || !$this->ciclo_escolar_id || !$this->generacion_id || !$this->grado_id) {
            $this->semestres = collect();
            return;
        }

        $this->semestres = app(ContextoEscolarService::class)->semestres(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: $this->generacion_id,
            gradoId: $this->grado_id,
        );
    }

    #[Computed]
    public function gruposDisponibles(): Collection
    {
        $filtrarContexto = $this->alcance !== 'nivel';

        return app(ContextoEscolarService::class)->grupos(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: $filtrarContexto ? $this->generacion_id : null,
            gradoId: $filtrarContexto ? $this->grado_id : null,
            semestreId: $filtrarContexto ? $this->semestre_id : null,
            bachillerato: $this->esBachillerato,
        );
    }

    #[Computed]
    public function horasRango(): Collection
    {
        if ($this->horas->isEmpty()) {
            return collect();
        }

        $indiceInicio = $this->horas->search(fn (Hora $hora) => (int) $hora->id === (int) $this->hora_inicio_id);
        $indiceFin = $this->horas->search(fn (Hora $hora) => (int) $hora->id === (int) $this->hora_fin_id);

        if ($indiceInicio === false || $indiceFin === false) {
            return $this->horas;
        }

        $inicio = min($indiceInicio, $indiceFin);
        $fin = max($indiceInicio, $indiceFin);

        return $this->horas->slice($inicio, $fin - $inicio + 1)->values();
    }

    #[Computed]
    public function cantidadSeleccionada(): int
    {
        return collect($this->grupos_seleccionados)
            ->filter()
            ->unique()
            ->count();
    }

    #[Computed]
    public function puedeGenerar(): bool
    {
        if (!$this->nivel || !$this->ciclo_escolar_id || $this->horasRango->isEmpty()) {
            return false;
        }

        if ($this->alcance === 'nivel') {
            return $this->gruposDisponibles->isNotEmpty();
        }

        if (!$this->generacion_id || !$this->grado_id) {
            return false;
        }

        if ($this->esBachillerato && !$this->semestre_id) {
            return false;
        }

        if ($this->alcance === 'grupos') {
            return $this->cantidadSeleccionada > 0;
        }

        return $this->gruposDisponibles->isNotEmpty();
    }

    #[Computed]
    public function urlVistaPrevia(): string
    {
        return route('generales.horarios-vacios.pdf', [
            'slug_nivel' => $this->slug_nivel,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'alcance' => $this->alcance,
            'generacion_id' => $this->generacion_id,
            'grado_id' => $this->grado_id,
            'semestre_id' => $this->semestre_id,
            'grupos_seleccionados' => $this->grupos_seleccionados,
            'hora_inicio_id' => $this->hora_inicio_id,
            'hora_fin_id' => $this->hora_fin_id,
            'estilo_celda' => $this->estilo_celda,
        ]);
    }

    public function etiquetaGrupo(Grupo $grupo): string
    {
        $partes = [
            $grupo->grado?->nombre,
            $this->esBachillerato && $grupo->semestre ? 'Sem. ' . $grupo->semestre->numero : null,
            $grupo->asignacionGrupo?->nombre,
        ];

        return trim(collect($partes)->filter()->implode(' · '));
    }

    public function render()
    {
        return view('livewire.accion.generales.horarios-vacios');
    }
}
