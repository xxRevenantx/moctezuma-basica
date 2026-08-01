<?php

namespace App\Livewire\Tutor;

use App\Exports\TutorsExport;
use App\Models\Tutor;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class MostrarTutor extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $buscar = '';
    public string $estado = 'activos';
    public string $funcion = 'todas';
    public string $ordenCampo = 'id';
    public string $ordenDireccion = 'desc';

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccess('alumnos.consultar'), 403);
    }

    protected $queryString = [
        'buscar' => ['except' => ''],
        'estado' => ['except' => 'activos'],
        'funcion' => ['except' => 'todas'],
    ];

    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedEstado(): void
    {
        $this->resetPage();
    }

    public function updatedFuncion(): void
    {
        $this->resetPage();
    }

    public function ordenarPor(string $campo): void
    {
        $permitidos = ['id', 'curp', 'nombre', 'apellido_paterno', 'telefono_celular', 'correo_electronico', 'activo', 'updated_at'];

        if (! in_array($campo, $permitidos, true)) {
            return;
        }

        if ($this->ordenCampo === $campo) {
            $this->ordenDireccion = $this->ordenDireccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenCampo = $campo;
            $this->ordenDireccion = 'asc';
        }

        $this->resetPage();
    }

    public function archivar(int $id): void
    {
        abort_unless(auth()->user()?->canAccess('alumnos.eliminar'), 403);
        $tutor = Tutor::query()
            ->withCount('relacionesActivas')
            ->find($id);

        if (! $tutor) {
            return;
        }

        if ((int) $tutor->relaciones_activas_count > 0) {
            $this->dispatch('swal', [
                'title' => 'No se puede archivar',
                'text' => 'Este responsable todavía tiene ' . (int) $tutor->relaciones_activas_count
                    . ' relación(es) activa(s). Retíralo primero de cada alumno para conservar un historial coherente.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
            return;
        }

        $tutor->forceFill([
            'activo' => false,
            'archivado_at' => now(),
            'archivado_por' => auth()->id(),
        ])->save();

        $this->dispatch('swal', [
            'title' => 'Responsable archivado',
            'text' => 'No aparecerá en nuevas búsquedas, pero sus relaciones históricas se conservan.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function reactivar(int $id): void
    {
        abort_unless(auth()->user()?->canAccess('alumnos.editar'), 403);
        $tutor = Tutor::query()->find($id);

        if (! $tutor) {
            return;
        }

        $tutor->forceFill([
            'activo' => true,
            'archivado_at' => null,
            'archivado_por' => null,
        ])->save();

        $this->dispatch('swal', [
            'title' => 'Responsable reactivado',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function exportarTutores()
    {
        abort_unless(auth()->user()?->canAccess('alumnos.consultar'), 403);

        return Excel::download(
            new TutorsExport(
                $this->consulta()->get(),
                (bool) auth()->user()?->canAccess('alumnos.responsables_sensibles'),
            ),
            'responsables_' . now()->format('Y_m_d_H_i_s') . '.xlsx',
        );
    }

    private function consulta(): Builder
    {
        $funcion = $this->funcion;
        $funcionesSensibles = ['tutores_legales', 'autorizados_recoger'];

        if (in_array($funcion, $funcionesSensibles, true)
            && ! auth()->user()?->canAccess('alumnos.responsables_sensibles')) {
            $funcion = 'todas';
        }

        return Tutor::query()
            ->withCount([
                'relaciones as relaciones_total_count',
                'relacionesActivas as relaciones_activas_count',
            ])
            ->when($this->estado === 'activos', fn (Builder $query) => $query->where('activo', true))
            ->when($this->estado === 'archivados', fn (Builder $query) => $query->where('activo', false))
            ->when($this->estado === 'sin_alumnos', fn (Builder $query) => $query->doesntHave('relacionesActivas'))
            ->when($funcion === 'principales', fn (Builder $query) => $query->whereHas(
                'relaciones',
                fn (Builder $relacion) => $relacion->where('activo', true)->where('es_principal', true),
            ))
            ->when($funcion === 'tutores_legales', fn (Builder $query) => $query->whereHas(
                'relaciones',
                fn (Builder $relacion) => $relacion->where('activo', true)->where('es_tutor_legal', true),
            ))
            ->when($funcion === 'emergencias', fn (Builder $query) => $query->whereHas(
                'relaciones',
                fn (Builder $relacion) => $relacion->where('activo', true)->where('contacto_emergencia', true),
            ))
            ->when($funcion === 'autorizados_recoger', fn (Builder $query) => $query->whereHas(
                'relaciones',
                fn (Builder $relacion) => $relacion->where('activo', true)->where('autorizado_recoger', true),
            ))
            ->when($funcion === 'responsables_economicos', fn (Builder $query) => $query->whereHas(
                'relaciones',
                fn (Builder $relacion) => $relacion->where('activo', true)->where('responsable_economico', true),
            ))
            ->when($this->buscar, function (Builder $query): void {
                $buscar = '%' . trim($this->buscar) . '%';
                $query->where(function (Builder $q) use ($buscar): void {
                    $q->where('curp', 'like', mb_strtoupper($buscar))
                        ->orWhere('identificador_alternativo', 'like', $buscar)
                        ->orWhere('nombre', 'like', $buscar)
                        ->orWhere('apellido_paterno', 'like', $buscar)
                        ->orWhere('apellido_materno', 'like', $buscar)
                        ->orWhere('telefono_celular', 'like', $buscar)
                        ->orWhere('telefono_casa', 'like', $buscar)
                        ->orWhere('correo_electronico', 'like', $buscar)
                        ->orWhere('ciudad', 'like', $buscar)
                        ->orWhere('municipio', 'like', $buscar)
                        ->orWhere('estado', 'like', $buscar)
                        ->orWhereHas('relaciones', fn (Builder $relacion) => $relacion->where('parentesco', 'like', $buscar));
                });
            })
            ->orderBy($this->ordenCampo, $this->ordenDireccion);
    }

    #[On('refreshTutor')]
    public function render()
    {
        return view('livewire.tutor.mostrar-tutor', [
            'tutores' => $this->consulta()->paginate(10),
        ]);
    }
}
