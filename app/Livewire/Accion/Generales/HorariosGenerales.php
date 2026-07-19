<?php

namespace App\Livewire\Accion\Generales;

use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Models\CicloEscolar;
use App\Services\HorarioGeneralBuilder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class HorariosGenerales extends Component
{
    public string $slug_nivel = '';

    public ?Nivel $nivel = null;
    public bool $esBachillerato = false;

    public Collection $ciclosEscolares;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $semestres;
    public Collection $grupos;

    public ?int $ciclo_escolar_id = null;
    public string $alcance = 'nivel';
    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;

    /** Filtros aplicados a la tabla concentrada y al PDF. */
    public ?int $filtro_grado_id = null;
    public ?int $filtro_grupo_id = null;
    public ?int $filtro_dia_id = null;
    public string $filtro_materia = '';

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

        $this->ciclo_escolar_id = $this->ciclosEscolares->first()?->id;

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('status', 1)
            ->orderByDesc('anio_ingreso')
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso']);

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);

        $this->semestres = collect();
        $this->grupos = collect();
    }

    public function updatedAlcance(): void
    {
        $this->generacion_id = null;
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->semestres = collect();
        $this->grupos = collect();
        $this->reiniciarFiltrosTabla();
    }

    public function updatedCicloEscolarId(): void
    {
        $this->grupo_id = null;
        $this->cargarGrupos();
        $this->reiniciarFiltrosTabla();
    }

    public function updatedGeneracionId(): void
    {
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->semestres = collect();
        $this->grupos = collect();
        $this->reiniciarFiltrosTabla();
    }

    public function updatedGradoId(): void
    {
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->cargarSemestres();
        $this->cargarGrupos();
        $this->reiniciarFiltrosTabla();
    }

    public function updatedSemestreId(): void
    {
        $this->grupo_id = null;
        $this->cargarGrupos();
        $this->reiniciarFiltrosTabla();
    }

    public function updatedGrupoId(): void
    {
        $this->reiniciarFiltrosTabla();
    }

    public function updatedFiltroGradoId(): void
    {
        if (
            $this->filtro_grupo_id
            && !$this->gruposTabla->contains(
                fn(Grupo $grupo) => (int) $grupo->id === (int) $this->filtro_grupo_id
            )
        ) {
            $this->filtro_grupo_id = null;
        }

        $this->filtro_materia = '';
    }

    public function updatedFiltroGrupoId(): void
    {
        $this->filtro_materia = '';
    }

    public function limpiarFiltros(): void
    {
        $this->alcance = 'nivel';
        $this->ciclo_escolar_id = $this->ciclosEscolares->first()?->id;
        $this->generacion_id = null;
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->semestres = collect();
        $this->grupos = collect();
        $this->reiniciarFiltrosTabla();
    }

    public function limpiarFiltrosTabla(): void
    {
        $this->reiniciarFiltrosTabla();
    }

    private function reiniciarFiltrosTabla(): void
    {
        $this->filtro_grado_id = null;
        $this->filtro_grupo_id = null;
        $this->filtro_dia_id = null;
        $this->filtro_materia = '';
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

    private function cargarGrupos(): void
    {
        $this->grupos = collect();

        if (
            $this->alcance !== 'grupo'
            || !$this->ciclo_escolar_id
            || !$this->generacion_id
            || !$this->grado_id
            || ($this->esBachillerato && !$this->semestre_id)
        ) {
            return;
        }

        $consulta = Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->where('nivel_id', $this->nivel->id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $this->grado_id)
            ->whereHas('horarios', function ($query) {
                $query
                    ->where('nivel_id', $this->nivel->id)
                    ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                    ->where(function ($actividadQuery) {
                        $actividadQuery
                            ->whereNotNull('asignacion_materia_id')
                            ->orWhereNotNull('taller_sesion_id');
                    });
            });

        $this->semestre_id
            ? $consulta->where('semestre_id', $this->semestre_id)
            : $consulta->whereNull('semestre_id');

        $this->grupos = $consulta
            ->get([
                'id',
                'asignacion_grupo_id',
                'nivel_id',
                'grado_id',
                'generacion_id',
                'semestre_id',
            ])
            ->sortBy(fn(Grupo $grupo) => Str::lower(Str::ascii(
                trim((string) ($grupo->asignacionGrupo?->nombre ?? ''))
            )))
            ->values();
    }

    private function filtrosAlcanceCompletos(): bool
    {
        if (
            !$this->nivel
            || !$this->ciclo_escolar_id
            || !in_array($this->alcance, ['nivel', 'grado', 'grupo'], true)
        ) {
            return false;
        }

        if ($this->alcance === 'nivel') {
            return true;
        }

        if (!$this->generacion_id || !$this->grado_id) {
            return false;
        }

        if ($this->esBachillerato && !$this->semestre_id) {
            return false;
        }

        return $this->alcance !== 'grupo' || filled($this->grupo_id);
    }

    private function consultaGruposConHorario(): Builder
    {
        $consulta = Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'generacion:id,nivel_id,anio_ingreso,anio_egreso',
                'grado:id,nivel_id,nombre,orden',
                'semestre:id,grado_id,numero,orden_global',
            ])
            ->where('nivel_id', $this->nivel->id)
            ->whereHas('horarios', function ($query) {
                $query
                    ->where('nivel_id', $this->nivel->id)
                    ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                    ->where(function ($actividadQuery) {
                        $actividadQuery
                            ->whereNotNull('asignacion_materia_id')
                            ->orWhereNotNull('taller_sesion_id');
                    });
            });

        if (in_array($this->alcance, ['grado', 'grupo'], true)) {
            $consulta
                ->where('generacion_id', $this->generacion_id)
                ->where('grado_id', $this->grado_id);

            $this->semestre_id
                ? $consulta->where('semestre_id', $this->semestre_id)
                : $consulta->whereNull('semestre_id');
        }

        if ($this->alcance === 'grupo') {
            $consulta->whereKey($this->grupo_id);
        }

        return $consulta;
    }

    #[Computed]
    public function gruposConHorario(): Collection
    {
        if (!$this->filtrosAlcanceCompletos()) {
            return collect();
        }

        return $this->consultaGruposConHorario()
            ->get()
            ->sortBy(fn(Grupo $grupo) => $this->claveOrdenGrupo($grupo))
            ->values();
    }

    #[Computed]
    public function gradosTabla(): Collection
    {
        return $this->gruposConHorario
            ->pluck('grado')
            ->filter()
            ->unique('id')
            ->sortBy(fn($grado) => sprintf(
                '%06d-%s',
                (int) ($grado->orden ?? 999999),
                Str::lower(Str::ascii((string) ($grado->nombre ?? '')))
            ))
            ->values();
    }

    #[Computed]
    public function gruposTabla(): Collection
    {
        return $this->gruposConHorario
            ->when(
                $this->filtro_grado_id,
                fn(Collection $grupos) => $grupos->where('grado_id', $this->filtro_grado_id)
            )
            ->values();
    }

    #[Computed]
    public function tablaGeneral(): ?array
    {
        if (!$this->nivel || !$this->ciclo_escolar_id || $this->gruposConHorario->isEmpty()) {
            return null;
        }

        $ciclo = $this->ciclosEscolares->first(
            fn(CicloEscolar $item) => (int) $item->id === (int) $this->ciclo_escolar_id
        );

        if (!$ciclo) {
            return null;
        }

        return app(HorarioGeneralBuilder::class)->construir(
            nivel: $this->nivel,
            cicloEscolar: $ciclo,
            grupos: $this->gruposConHorario,
            filtros: [
                'grado_id' => $this->filtro_grado_id,
                'grupo_id' => $this->filtro_grupo_id,
                'dia_id' => $this->filtro_dia_id,
                'materia' => $this->filtro_materia,
            ],
        );
    }

    #[Computed]
    public function diasTabla(): Collection
    {
        return collect($this->tablaGeneral['dias_opciones'] ?? []);
    }

    #[Computed]
    public function materiasTabla(): Collection
    {
        return collect($this->tablaGeneral['materias_opciones'] ?? []);
    }

    #[Computed]
    public function puedeDescargar(): bool
    {
        return $this->filtrosAlcanceCompletos()
            && $this->gruposConHorario->isNotEmpty()
            && ($this->tablaGeneral['total_actividades'] ?? 0) > 0;
    }

    #[Computed]
    public function totalGruposConHorario(): int
    {
        return $this->gruposConHorario->count();
    }

    #[Computed]
    public function totalGruposVisibles(): int
    {
        return (int) ($this->tablaGeneral['total_grupos'] ?? 0);
    }

    #[Computed]
    public function urlDescarga(): ?string
    {
        if (!$this->puedeDescargar) {
            return null;
        }

        return route('generales.horarios.pdf', array_filter([
            'slug_nivel' => $this->slug_nivel,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'alcance' => $this->alcance,
            'generacion_id' => $this->generacion_id,
            'grado_id' => $this->grado_id,
            'semestre_id' => $this->semestre_id,
            'grupo_id' => $this->grupo_id,
            'filtro_grado_id' => $this->filtro_grado_id,
            'filtro_grupo_id' => $this->filtro_grupo_id,
            'filtro_dia_id' => $this->filtro_dia_id,
            'filtro_materia' => $this->filtro_materia,
        ], fn($valor) => $valor !== null && $valor !== ''));
    }

    #[Computed]
    public function textoAlcance(): string
    {
        return match ($this->alcance) {
            'grado' => 'Todos los grupos del grado seleccionado',
            'grupo' => 'Grupo seleccionado',
            default => 'Todos los grupos con horario del nivel',
        };
    }

    public function etiquetaGrupo(Grupo $grupo): string
    {
        return app(HorarioGeneralBuilder::class)->etiquetaGrupo($grupo);
    }

    public function formatoHora(?string $hora): string
    {
        if (!$hora) {
            return '—';
        }

        foreach (['H:i:s', 'H:i'] as $formato) {
            try {
                return Carbon::createFromFormat($formato, $hora)->format('h:i A');
            } catch (\Throwable) {
                // Se intenta el siguiente formato.
            }
        }

        return $hora;
    }

    private function claveOrdenGrupo(Grupo $grupo): string
    {
        return sprintf(
            '%06d-%06d-%s-%06d-%06d',
            (int) ($grupo->grado?->orden ?? 999999),
            (int) ($grupo->semestre?->orden_global ?? $grupo->semestre?->numero ?? 0),
            Str::lower(Str::ascii(trim((string) ($grupo->asignacionGrupo?->nombre ?? '')))),
            (int) ($grupo->generacion?->anio_ingreso ?? 999999),
            (int) $grupo->id
        );
    }

    public function render()
    {
        return view('livewire.accion.generales.horarios-generales');
    }
}
