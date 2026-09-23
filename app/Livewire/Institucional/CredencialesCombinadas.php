<?php

namespace App\Livewire\Institucional;

use App\Models\CicloEscolar;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Services\ContextoCicloEscolarSesion;
use App\Services\CredencialCopiasService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class CredencialesCombinadas extends Component
{
    use WithPagination;

    public string $ciclo = '';
    public string $nivel = '';
    public string $grado = '';
    public string $grupo = '';
    public string $buscar = '';
    public string $situacion = 'activos';
    public int $copias = 1;

    /** Mapa inscripción => copias. Solo las acciones del servidor lo modifican. */
    #[Locked]
    public array $seleccion = [];

    public function boot(): void
    {
        abort_unless((bool) auth()->user()?->is_admin, 403);
    }

    public function mount(): void
    {
        $this->ciclo = (string) app(ContextoCicloEscolarSesion::class)->resolver($this->ciclos);
    }

    #[Computed]
    public function ciclos(): Collection
    {
        return CicloEscolar::query()->orderByDesc('es_actual')->orderByDesc('inicio_anio')
            ->orderByDesc('id')->get();
    }

    #[Computed]
    public function niveles(): Collection
    {
        return Nivel::query()->orderBy('id')->get();
    }

    protected function base(): Builder
    {
        return Inscripcion::query()->where('ciclo_escolar_id', (int) $this->ciclo)
            ->whereHas('nivel');
    }

    protected function filtrados(bool $conGrado = true, bool $conGrupo = true, bool $conBusqueda = true): Builder
    {
        $query = $this->base();
        if ($this->situacion === 'activos') {
            $query->visiblesEnListas();
        } elseif ($this->situacion === 'bajas') {
            $query->whereIn('estatus', Inscripcion::ESTATUS_BAJA);
        } elseif ($this->situacion === 'egresados') {
            $query->where('estatus', Inscripcion::ESTATUS_EGRESADO);
        } elseif ($this->situacion !== 'todos') {
            $query->whereRaw('1 = 0');
        }
        $query->when($this->nivel !== '', fn ($q) => $q->where('nivel_id', (int) $this->nivel));
        if ($conGrado && $this->grado !== '') {
            $query->where('grado_id', (int) $this->grado);
        }
        if ($conGrupo && $this->grupo !== '') {
            $query->where('grupo_id', (int) $this->grupo);
        }
        if ($conBusqueda && trim($this->buscar) !== '') {
            $texto = '%' . trim($this->buscar) . '%';
            $query->where(fn ($q) => $q->where('matricula', 'like', $texto)
                ->orWhere('curp', 'like', $texto)
                ->orWhereRaw("CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?", [$texto])
                ->orWhereRaw("CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?", [$texto]));
        }
        return $query;
    }

    #[Computed]
    public function grados(): Collection
    {
        return \App\Models\Grado::query()->with('nivel')
            ->whereIn('id', $this->filtrados(false, false, false)->select('grado_id'))
            ->orderBy('nivel_id')->orderBy('orden')->orderBy('nombre')->get();
    }

    #[Computed]
    public function grupos(): Collection
    {
        return \App\Models\Grupo::query()->with(['asignacionGrupo', 'grado', 'generacion', 'semestre'])
            ->whereIn('id', $this->filtrados(true, false, false)->select('grupo_id'))
            ->orderBy('grado_id')->orderBy('id')->get();
    }

    protected function ordenar(Collection $alumnos): Collection
    {
        return $alumnos->sortBy([
            fn ($a, $b) => (int) $a->nivel_id <=> (int) $b->nivel_id,
            fn ($a, $b) => (int) ($a->grado?->orden ?? $a->grado_id) <=> (int) ($b->grado?->orden ?? $b->grado_id),
            fn ($a, $b) => strnatcasecmp($a->grupo?->asignacionGrupo?->nombre ?? '', $b->grupo?->asignacionGrupo?->nombre ?? ''),
            fn ($a, $b) => strcasecmp($this->nombre($a), $this->nombre($b)),
            fn ($a, $b) => $a->id <=> $b->id,
        ])->values();
    }

    #[Computed]
    public function seleccionados(): Collection
    {
        return $this->ordenar($this->base()->whereIn('id', array_keys($this->seleccion))
            ->with(['nivel.director', 'grado', 'grupo.asignacionGrupo'])->get());
    }

    public function updated(string $propiedad): void
    {
        $this->resetValidation();
        if ($propiedad === 'ciclo') {
            $this->seleccion = [];
            $this->nivel = '';
        }
        if (in_array($propiedad, ['ciclo', 'nivel', 'situacion'], true)) {
            $this->grado = '';
            $this->grupo = '';
        }
        if ($propiedad === 'grado') {
            $this->grupo = '';
        }
        if (in_array($propiedad, ['ciclo', 'nivel', 'grado', 'grupo', 'buscar', 'situacion'], true)) {
            $this->resetPage('credencialesPagina');
        }
        if ($propiedad === 'copias') {
            $this->copias = max(1, min($this->maxCopias(), $this->copias));
        }
    }

    public function agregar(int $id): void
    {
        if ($this->filtrados()->whereKey($id)->exists() && ! isset($this->seleccion[$id])) {
            $this->seleccion[$id] = max(1, min($this->maxCopias(), $this->copias));
        }
        unset($this->seleccionados);
    }

    public function agregarResultados(): void
    {
        // Incluye todas las páginas. Agregar otra vez no duplica ni cambia copias.
        foreach ($this->filtrados()->pluck('id') as $id) {
            $this->seleccion[$id] ??= max(1, min($this->maxCopias(), $this->copias));
        }
        unset($this->seleccionados);
    }

    public function quitar(int $id): void
    {
        unset($this->seleccion[$id], $this->seleccionados);
    }

    public function ajustarCopias(int $id, int $cambio): void
    {
        if (isset($this->seleccion[$id]) && in_array($cambio, [-1, 1], true)) {
            $this->seleccion[$id] = max(1, min($this->maxCopias(), $this->seleccion[$id] + $cambio));
        }
    }

    public function aplicarCopias(): void
    {
        $cantidad = max(1, min($this->maxCopias(), $this->copias));
        $this->seleccion = array_fill_keys(array_keys($this->seleccion), $cantidad);
    }

    public function limpiarSeleccion(): void
    {
        $this->seleccion = [];
        unset($this->seleccionados);
    }

    public function limpiarFiltros(): void
    {
        $this->reset('nivel', 'grado', 'grupo', 'buscar', 'situacion');
        $this->resetPage('credencialesPagina');
    }

    public function maxCopias(): int
    {
        return app(CredencialCopiasService::class)->maximoPorAlumno();
    }

    public function nombre($alumno): string
    {
        return trim($alumno->apellido_paterno . ' ' . $alumno->apellido_materno . ' ' . $alumno->nombre);
    }

    public function descargar()
    {
        $cicloEscolar = CicloEscolar::query()->findOrFail((int) $this->ciclo);
        $alumnosBase = $this->seleccionados;
        if ($alumnosBase->isEmpty()) {
            $this->addError('seleccion', 'Agrega al menos un alumno para descargar.');
            return;
        }
        if ($alumnosBase->count() !== count($this->seleccion)) {
            $this->seleccion = array_intersect_key($this->seleccion, array_flip($alumnosBase->modelKeys()));
            $this->addError('seleccion', 'Algunos alumnos ya no están disponibles en este ciclo. Se retiraron de la selección; revisa el resumen y vuelve a descargar.');
            return;
        }
        $peticion = Request::create('/', 'GET', [
            'copias_individuales' => collect($this->seleccion)->map(fn ($cantidad, $id) => $id . ':' . $cantidad)->implode(','),
        ]);
        $alumnos = app(CredencialCopiasService::class)->expandir($alumnosBase, $peticion);
        $fotosDataUri = $alumnosBase->mapWithKeys(fn ($alumno) => [$alumno->id => $alumno->foto_data_uri]);
        $contenido = Pdf::loadView('pdf.credenciales_pdf', [
            'alumnos' => $alumnos,
            'cicloEscolar' => $cicloEscolar,
            'fotosDataUri' => $fotosDataUri,
            'combinado' => true,
        ])->setPaper('letter', 'portrait')->output();

        return response()->streamDownload(function () use ($contenido): void {
            echo $contenido;
        }, 'credenciales_combinadas_' . now()->format('Ymd_His') . '.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function render()
    {
        return view('livewire.institucional.credenciales-combinadas', [
            'resultados' => $this->filtrados()->with(['nivel', 'grado', 'grupo.asignacionGrupo'])
                ->orderBy('nivel_id')->orderBy('grado_id')->orderBy('grupo_id')
                ->orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombre')
                ->orderBy('id')->paginate(25, ['*'], 'credencialesPagina'),
        ]);
    }
}
