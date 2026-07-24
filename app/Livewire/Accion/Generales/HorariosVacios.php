<?php

namespace App\Livewire\Accion\Generales;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Nivel;
use App\Models\Semestre;
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

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderByDesc('status')
            ->orderByDesc('anio_ingreso')
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso', 'nombre', 'status']);

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);

        $this->semestres = collect();

        $this->horas = Hora::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('hora_inicio')
            ->get(['id', 'nivel_id', 'hora_inicio', 'hora_fin', 'orden']);

        $this->ciclo_escolar_id = $this->ciclosEscolares->first()?->id;
        $this->hora_inicio_id = $this->horas->first()?->id;
        $this->hora_fin_id = $this->horas->last()?->id;
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
        $this->grupos_seleccionados = [];
    }

    public function updatedGeneracionId(): void
    {
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->semestres = collect();
        $this->grupos_seleccionados = [];
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

    private function cargarSemestres(): void
    {
        if (!$this->esBachillerato || !$this->grado_id) {
            $this->semestres = collect();
            return;
        }

        $this->semestres = Semestre::query()
            ->where('grado_id', $this->grado_id)
            ->orderBy('orden_global')
            ->orderBy('numero')
            ->get(['id', 'grado_id', 'numero', 'orden_global']);
    }

    #[Computed]
    public function gruposDisponibles(): Collection
    {
        $consulta = Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'grado:id,nombre,orden',
                'generacion:id,nombre,anio_ingreso,anio_egreso,status',
                'semestre:id,numero,orden_global',
            ])
            ->where('nivel_id', $this->nivel?->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->whereNull('deleted_at');

        if ($this->alcance !== 'nivel') {
            if ($this->generacion_id) {
                $consulta->where('generacion_id', $this->generacion_id);
            }

            if ($this->grado_id) {
                $consulta->where('grado_id', $this->grado_id);
            }

            if ($this->esBachillerato) {
                if ($this->semestre_id) {
                    $consulta->where('semestre_id', $this->semestre_id);
                } elseif ($this->grado_id) {
                    $consulta->whereNotNull('semestre_id');
                }
            }
        }

        return $consulta
            ->get(['id', 'ciclo_escolar_id', 'asignacion_grupo_id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id'])
            ->sortBy(function (Grupo $grupo) {
                return sprintf(
                    '%06d-%06d-%s-%06d',
                    (int) ($grupo->grado?->orden ?? 999999),
                    (int) ($grupo->semestre?->orden_global ?? $grupo->semestre?->numero ?? 0),
                    Str::lower(Str::ascii(trim((string) ($grupo->asignacionGrupo?->nombre ?? '')))),
                    (int) $grupo->id,
                );
            })
            ->values();
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
