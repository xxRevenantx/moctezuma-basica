<?php

namespace App\Livewire;

use App\Models\CicloEscolar;
use App\Models\Nivel;
use App\Services\EstadisticaAlumnosGruposService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class EstadisticaAlumnosGrupos extends Component
{
    public Collection $ciclosEscolares;
    public Collection $niveles;

    public ?int $ciclo_escolar_id = null;
    public ?int $nivel_id = null;

    /** @var array<string,mixed> */
    public array $datos = [];

    public ?string $error = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccess('alumnos.consultar'), 403);

        $this->ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual']);

        $this->niveles = Nivel::query()
            ->whereIn('slug', ['preescolar', 'primaria', 'secundaria'])
            ->orderBy('id')
            ->get(['id', 'nombre', 'slug', 'cct', 'color']);

        $this->ciclo_escolar_id = $this->ciclosEscolares->firstWhere('es_actual', true)?->id
            ?? $this->ciclosEscolares->first()?->id;
        $this->nivel_id = $this->niveles->firstWhere('slug', 'primaria')?->id
            ?? $this->niveles->first()?->id;

        $this->cargarDatos();
    }

    public function updatedCicloEscolarId($value): void
    {
        $this->ciclo_escolar_id = $value ? (int) $value : null;
        $this->cargarDatos();
    }

    public function updatedNivelId($value): void
    {
        $this->nivel_id = $value ? (int) $value : null;
        $this->cargarDatos();
    }

    public function actualizar(): void
    {
        $this->cargarDatos();
    }

    private function cargarDatos(): void
    {
        $this->error = null;
        $this->datos = [];

        if (! $this->ciclo_escolar_id || ! $this->nivel_id) {
            return;
        }

        try {
            $this->datos = app(EstadisticaAlumnosGruposService::class)->generar(
                $this->ciclo_escolar_id,
                $this->nivel_id,
            );
        } catch (ValidationException $exception) {
            $this->error = collect($exception->errors())->flatten()->first();
        } catch (\Throwable $exception) {
            report($exception);
            $this->error = 'No fue posible construir el desglose estadístico. Revisa la integridad del ciclo y del historial de alumnos.';
        }
    }

    public function render()
    {
        return view('livewire.estadistica-alumnos-grupos');
    }
}
