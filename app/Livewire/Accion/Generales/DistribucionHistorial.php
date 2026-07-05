<?php

namespace App\Livewire\Accion\Generales;

use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Services\DistribucionEscolarService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

class DistribucionHistorial extends Component
{
    public string $slug_nivel = '';
    public ?Nivel $nivel = null;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $semestres;
    public Collection $grupos;

    public string $generacion_id = '';
    public string $grado_id = '';
    public string $semestre_id = '';
    public string $grupo_id = '';
    public string $estado = 'todos';
    public bool $solo_ya_no_estan = false;

    public string $busqueda_nominal = '';
    public string $genero_nominal = 'todos';
    public string $orden_nominal = 'nombre_asc';

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('status', true)
            ->orderByDesc('anio_ingreso')
            ->get();

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->get();

        $this->semestres = collect();
        $this->grupos = collect();
    }

    public function updatedGeneracionId(): void
    {
        $this->cargarGrupos();
    }

    public function updatedGradoId(): void
    {
        $this->semestre_id = '';
        $this->semestres = $this->grado_id !== ''
            ? Semestre::query()
                ->where('grado_id', $this->grado_id)
                ->orderBy('orden_global')
                ->orderBy('numero')
                ->get()
            : collect();

        $this->cargarGrupos();
    }

    public function updatedSemestreId(): void
    {
        $this->cargarGrupos();
    }

    private function cargarGrupos(): void
    {
        $this->grupo_id = '';

        $this->grupos = Grupo::query()
            ->with('asignacionGrupo')
            ->where('nivel_id', $this->nivel->id)
            ->whereHas('generacion', fn($query) => $query->where('status', true))
            ->when($this->generacion_id !== '', fn($query) => $query->where('generacion_id', $this->generacion_id))
            ->when($this->grado_id !== '', fn($query) => $query->where('grado_id', $this->grado_id))
            ->when($this->semestre_id !== '', fn($query) => $query->where('semestre_id', $this->semestre_id))
            ->get()
            ->sortBy(fn($grupo) => $grupo->asignacionGrupo?->nombre ?? $grupo->id)
            ->values();
    }

    public function getCategoriasProperty(): array
    {
        return app(DistribucionEscolarService::class)->categorias();
    }

    public function getBloquesProperty(): Collection
    {
        return app(DistribucionEscolarService::class)->bloques($this->nivel, $this->filtros());
    }

    public function getListadoProperty(): Collection
    {
        return app(DistribucionEscolarService::class)->listadoCompleto($this->nivel, $this->filtros());
    }

    public function getListadoFiltradoProperty(): Collection
    {
        $listado = $this->getListadoProperty();
        $termino = $this->normalizar($this->busqueda_nominal);

        if ($termino !== '') {
            $listado = $listado->filter(function (array $fila) use ($termino): bool {
                $texto = $this->normalizar(implode(' ', [
                    $fila['alumno'] ?? '',
                    $fila['matricula'] ?? '',
                    $fila['curp'] ?? '',
                    $fila['generacion'] ?? '',
                    $fila['grado'] ?? '',
                    $fila['semestre'] ?? '',
                    $fila['grupo'] ?? '',
                    $fila['estado_actual'] ?? '',
                ]));

                return str_contains($texto, $termino);
            });
        }

        if ($this->genero_nominal !== 'todos') {
            $listado = $listado->where('genero', $this->genero_nominal);
        }

        $listado = match ($this->orden_nominal) {
            'nombre_desc' => $listado->sortByDesc('alumno', SORT_NATURAL | SORT_FLAG_CASE),
            'generacion_desc' => $listado->sort(function (array $a, array $b): int {
                    $comparacion = strnatcasecmp((string) ($b['generacion'] ?? ''), (string) ($a['generacion'] ?? ''));

                    return $comparacion !== 0
                    ? $comparacion
                    : strnatcasecmp((string) ($a['alumno'] ?? ''), (string) ($b['alumno'] ?? ''));
                }),
            'estatus' => $listado->sort(function (array $a, array $b): int {
                    $comparacion = strnatcasecmp((string) ($a['estado_actual'] ?? ''), (string) ($b['estado_actual'] ?? ''));

                    return $comparacion !== 0
                    ? $comparacion
                    : strnatcasecmp((string) ($a['alumno'] ?? ''), (string) ($b['alumno'] ?? ''));
                }),
            default => $listado->sortBy('alumno', SORT_NATURAL | SORT_FLAG_CASE),
        };

        return $listado->values();
    }

    public function getResumenNominalProperty(): array
    {
        $listado = $this->getListadoFiltradoProperty();

        return [
            'total' => $listado->count(),
            'hombres' => $listado->where('genero', 'H')->count(),
            'mujeres' => $listado->where('genero', 'M')->count(),
            'activos' => $listado->whereIn('categoria_actual', ['activo', 'reingreso', 'no_promovido'])->count(),
            'no_activos' => $listado->whereNotIn('categoria_actual', ['activo', 'reingreso', 'no_promovido'])->count(),
        ];
    }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'generacion_id',
            'grado_id',
            'semestre_id',
            'grupo_id',
            'solo_ya_no_estan',
            'busqueda_nominal',
        ]);

        $this->estado = 'todos';
        $this->genero_nominal = 'todos';
        $this->orden_nominal = 'nombre_asc';
        $this->semestres = collect();
        $this->grupos = collect();
    }

    public function limpiarFiltrosNominales(): void
    {
        $this->busqueda_nominal = '';
        $this->genero_nominal = 'todos';
        $this->orden_nominal = 'nombre_asc';
    }

    public function getUrlPdfProperty(): string
    {
        return route('generales.distribucion.pdf', array_merge(
            ['slug_nivel' => $this->slug_nivel],
            $this->filtrosUrl()
        ));
    }

    public function getUrlExcelProperty(): string
    {
        return route('generales.distribucion.excel', array_merge(
            ['slug_nivel' => $this->slug_nivel],
            $this->filtrosUrl()
        ));
    }

    public function getUrlZipProperty(): string
    {
        return route('generales.distribucion.zip', array_merge(
            ['slug_nivel' => $this->slug_nivel],
            $this->filtrosUrl()
        ));
    }

    private function filtros(): array
    {
        return [
            'generacion_id' => $this->generacion_id !== '' ? (int) $this->generacion_id : null,
            'grado_id' => $this->grado_id !== '' ? (int) $this->grado_id : null,
            'semestre_id' => $this->semestre_id !== '' ? (int) $this->semestre_id : null,
            'grupo_id' => $this->grupo_id !== '' ? (int) $this->grupo_id : null,
            'estado' => $this->estado,
            'solo_ya_no_estan' => $this->solo_ya_no_estan,
        ];
    }

    private function filtrosUrl(): array
    {
        return array_filter(
            $this->filtros(),
            fn($valor) => $valor !== null && $valor !== '' && $valor !== false && $valor !== 'todos'
        );
    }

    private function normalizar(?string $valor): string
    {
        return Str::lower(Str::ascii(trim((string) $valor)));
    }

    public function render()
    {
        return view('livewire.accion.generales.distribucion-historial');
    }
}
