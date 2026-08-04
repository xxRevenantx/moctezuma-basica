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
    public ?int $expedienteTutorId = null;

    protected $queryString = [
        'buscar' => ['except' => ''],
        'estado' => ['except' => 'activos'],
        'funcion' => ['except' => 'todas'],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccess('alumnos.consultar'), 403);
    }

    public function updatingBuscar(): void
    {
        $this->cerrarExpediente();
        $this->resetPage();
    }

    public function updatedEstado(): void
    {
        $this->cerrarExpediente();
        $this->resetPage();
    }

    public function updatedFuncion(): void
    {
        $this->cerrarExpediente();
        $this->resetPage();
    }

    public function alternarExpediente(int $tutorId): void
    {
        abort_unless(
            auth()->user()?->is_admin
                || auth()->user()?->canAccess('alumnos.consultar')
                || auth()->user()?->canAccess('documentos.consultar')
                || auth()->user()?->canAccess('documentos.organizar'),
            403
        );
        abort_unless(Tutor::query()->whereKey($tutorId)->exists(), 404);

        $this->expedienteTutorId = $this->expedienteTutorId === $tutorId
            ? null
            : $tutorId;
    }

    #[On('cerrar-expediente-tutor-inline')]
    public function cerrarExpediente(?int $tutorId = null): void
    {
        if ($tutorId !== null && $this->expedienteTutorId !== $tutorId) {
            return;
        }

        $this->expedienteTutorId = null;
    }

    public function ordenarPor(string $campo): void
    {
        $permitidos = [
            'id',
            'curp',
            'nombre',
            'apellido_paterno',
            'telefono_celular',
            'correo_electronico',
            'activo',
            'updated_at',
        ];

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

        if (
            in_array($funcion, $funcionesSensibles, true)
            && ! auth()->user()?->canAccess('alumnos.responsables_sensibles')
        ) {
            $funcion = 'todas';
        }

        return Tutor::query()
            ->with([
                'relaciones' => fn($relacion) => $relacion
                    ->orderByDesc('activo')
                    ->orderByRaw('CASE WHEN fecha_fin IS NULL THEN 0 ELSE 1 END')
                    ->orderByDesc('es_principal')
                    ->orderBy('orden_contacto')
                    ->orderByDesc('id'),
                'relaciones.inscripcion' => fn($inscripcion) => $inscripcion->withTrashed(),
                'relaciones.inscripcion.nivel:id,nombre',
                'relaciones.inscripcion.grado:id,nombre',
                'relaciones.inscripcion.grupo:id,clave',
                'relaciones.inscripcion.semestre:id,numero',
                'relaciones.inscripcion.generacion:id,nombre,anio_ingreso,anio_egreso',
                'relaciones.inscripcion.cicloEscolar:id,inicio_anio,fin_anio',
            ])
            ->withCount([
                'relaciones as relaciones_total_count',
                'relacionesActivas as relaciones_activas_count',
            ])
            ->when($this->estado === 'activos', fn(Builder $query) => $query->where('activo', true))
            ->when($this->estado === 'archivados', fn(Builder $query) => $query->where('activo', false))
            ->when($this->estado === 'sin_alumnos', fn(Builder $query) => $query->doesntHave('relacionesActivas'))
            ->when($funcion === 'principales', fn(Builder $query) => $query->whereHas(
                'relaciones',
                fn(Builder $relacion) => $relacion->where('activo', true)->where('es_principal', true),
            ))
            ->when($funcion === 'tutores_legales', fn(Builder $query) => $query->whereHas(
                'relaciones',
                fn(Builder $relacion) => $relacion->where('activo', true)->where('es_tutor_legal', true),
            ))
            ->when($funcion === 'emergencias', fn(Builder $query) => $query->whereHas(
                'relaciones',
                fn(Builder $relacion) => $relacion->where('activo', true)->where('contacto_emergencia', true),
            ))
            ->when($funcion === 'autorizados_recoger', fn(Builder $query) => $query->whereHas(
                'relaciones',
                fn(Builder $relacion) => $relacion->where('activo', true)->where('autorizado_recoger', true),
            ))
            ->when($funcion === 'responsables_economicos', fn(Builder $query) => $query->whereHas(
                'relaciones',
                fn(Builder $relacion) => $relacion->where('activo', true)->where('responsable_economico', true),
            ))
            ->when(trim($this->buscar) !== '', function (Builder $query): void {
                $termino = trim($this->buscar);
                $buscar = '%' . $termino . '%';
                $buscarMayusculas = '%' . mb_strtoupper($termino) . '%';

                $query->where(function (Builder $q) use ($buscar, $buscarMayusculas): void {
                    $q->where('curp', 'like', $buscarMayusculas)
                        ->orWhere('identificador_alternativo', 'like', $buscar)
                        ->orWhere('nombre', 'like', $buscar)
                        ->orWhere('apellido_paterno', 'like', $buscar)
                        ->orWhere('apellido_materno', 'like', $buscar)
                        ->orWhereRaw(
                            "CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?",
                            [$buscar],
                        )
                        ->orWhere('telefono_celular', 'like', $buscar)
                        ->orWhere('telefono_casa', 'like', $buscar)
                        ->orWhere('correo_electronico', 'like', $buscar)
                        ->orWhere('ciudad', 'like', $buscar)
                        ->orWhere('municipio', 'like', $buscar)
                        ->orWhere('estado', 'like', $buscar)
                        ->orWhereHas('relaciones', function (Builder $relacion) use ($buscar, $buscarMayusculas): void {
                            $relacion->where(function (Builder $relacionQuery) use ($buscar, $buscarMayusculas): void {
                                $relacionQuery->where('parentesco', 'like', $buscar)
                                    ->orWhereHas('inscripcion', function (Builder $alumno) use ($buscar, $buscarMayusculas): void {
                                        $alumno->withTrashed()
                                            ->where(function (Builder $alumnoQuery) use ($buscar, $buscarMayusculas): void {
                                                $alumnoQuery->where('nombre', 'like', $buscar)
                                                    ->orWhere('apellido_paterno', 'like', $buscar)
                                                    ->orWhere('apellido_materno', 'like', $buscar)
                                                    ->orWhereRaw(
                                                        "CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?",
                                                        [$buscar],
                                                    )
                                                    ->orWhere('curp', 'like', $buscarMayusculas)
                                                    ->orWhere('matricula', 'like', $buscar)
                                                    ->orWhere('folio', 'like', $buscar);
                                            });
                                    });
                            });
                        });
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
