<?php

namespace App\Livewire\Accion;

use App\Exports\MatriculaExport;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Services\ContextoCicloEscolarSesion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class Generales extends Component
{
    public ?Nivel $nivel = null;
    public Collection $niveles;
    public Collection $ciclosEscolares;
    public Collection $grados;
    public Collection $generaciones;
    public Collection $semestres;
    public Collection $grupos;

    public string $slug_nivel = '';
    public string $ciclo_escolar_id = '';
    public string $generacion_id = '';
    public string $grado_id = '';
    public string $semestre_id = '';
    public string $grupo_id = '';

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()
            ->with('director')
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $this->niveles = Nivel::query()
            ->orderBy('id')
            ->get(['id', 'nombre', 'slug']);

        $this->ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->orderByDesc('fin_anio')
            ->orderByDesc('id')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual', 'cerrado_at']);

        $this->generaciones = collect();
        $this->grados = collect();
        $this->semestres = collect();
        $this->grupos = collect();

        $this->ciclo_escolar_id = (string) (app(ContextoCicloEscolarSesion::class)->resolver($this->ciclosEscolares) ?? '');
        $this->cargarFiltrosDependientes();
    }

    public function updatedCicloEscolarId($value): void
    {
        $this->ciclo_escolar_id = $this->cicloValido($value)
            ? (string) ((int) $value)
            : (string) (app(ContextoCicloEscolarSesion::class)->resolver($this->ciclosEscolares) ?? '');

        $this->generacion_id = '';
        $this->grado_id = '';
        $this->semestre_id = '';
        $this->grupo_id = '';

        app(ContextoCicloEscolarSesion::class)->recordar($this->ciclo_escolar_id);
        $this->cargarFiltrosDependientes();
    }

    public function updatedGeneracionId(): void
    {
        $this->grado_id = '';
        $this->semestre_id = '';
        $this->grupo_id = '';

        $this->cargarGrados();
        $this->cargarSemestres();
        $this->cargarGrupos();
    }

    public function updatedGradoId(): void
    {
        $this->semestre_id = '';
        $this->grupo_id = '';

        $this->cargarSemestres();
        $this->cargarGrupos();
    }

    public function updatedSemestreId(): void
    {
        $this->grupo_id = '';
        $this->cargarGrupos();
    }

    public function limpiarFiltroEstadistica(): void
    {
        $this->generacion_id = '';
        $this->grado_id = '';
        $this->semestre_id = '';
        $this->grupo_id = '';
        $this->cargarFiltrosDependientes();
    }

    public function getCicloSeleccionadoProperty(): ?CicloEscolar
    {
        if ($this->ciclo_escolar_id === '') {
            return null;
        }

        return $this->ciclosEscolares->firstWhere('id', (int) $this->ciclo_escolar_id);
    }

    public function getEsCicloVigenteProperty(): bool
    {
        $ciclo = $this->cicloSeleccionado;

        return (bool) ($ciclo?->es_actual && blank($ciclo?->cerrado_at));
    }

    public function getResumenProperty(): array
    {
        $registros = $this->registrosFiltrados();

        return [
            'total' => $registros->count(),
            'hombres' => $registros->filter(fn (InscripcionCiclo $registro): bool => $registro->inscripcion?->genero === 'H')->count(),
            'mujeres' => $registros->filter(fn (InscripcionCiclo $registro): bool => $registro->inscripcion?->genero === 'M')->count(),
            'activos' => $registros->filter(fn (InscripcionCiclo $registro): bool => $this->esActivoEnCiclo($registro))->count(),
            'bajas' => $registros->filter(fn (InscripcionCiclo $registro): bool => $this->categoriaRegistro($registro) === 'baja')->count(),
            'trasladados' => $registros->filter(fn (InscripcionCiclo $registro): bool => $this->categoriaRegistro($registro) === 'traslado')->count(),
            'suspendidos' => $registros->filter(fn (InscripcionCiclo $registro): bool => $this->categoriaRegistro($registro) === 'suspendido')->count(),
            'egresados' => $registros->filter(fn (InscripcionCiclo $registro): bool => $this->categoriaRegistro($registro) === 'egresado')->count(),
            'inactivos' => $registros->filter(fn (InscripcionCiclo $registro): bool => $this->categoriaRegistro($registro) === 'inactivo')->count(),
            'reingresos' => $registros->filter(fn (InscripcionCiclo $registro): bool => $this->estadoRegistro($registro) === 'reingreso')->count(),
        ];
    }

    public function getDistribucionEscolarProperty(): Collection
    {
        return $this->registrosFiltrados()
            ->groupBy(fn (InscripcionCiclo $registro): string => ($registro->grado_id ?: 0) . '|' . ($registro->semestre_id ?: 0) . '|' . ($registro->grupo_id ?: 0))
            ->map(function (Collection $grupo): array {
                /** @var InscripcionCiclo $primero */
                $primero = $grupo->first();

                return [
                    'grado' => $primero->grado?->nombre ?? 'Sin grado',
                    'semestre' => $primero->semestre?->numero,
                    'grupo' => $primero->grupo?->asignacionGrupo?->nombre ?? '—',
                    'hombres' => $grupo->filter(fn (InscripcionCiclo $registro): bool => $registro->inscripcion?->genero === 'H')->count(),
                    'mujeres' => $grupo->filter(fn (InscripcionCiclo $registro): bool => $registro->inscripcion?->genero === 'M')->count(),
                    'total' => $grupo->count(),
                    'activos' => $grupo->filter(fn (InscripcionCiclo $registro): bool => $this->esActivoEnCiclo($registro))->count(),
                    'bajas' => $grupo->filter(fn (InscripcionCiclo $registro): bool => $this->categoriaRegistro($registro) === 'baja')->count(),
                    'egresados' => $grupo->filter(fn (InscripcionCiclo $registro): bool => $this->categoriaRegistro($registro) === 'egresado')->count(),
                    'orden' => (int) ($primero->grado?->orden ?? 999),
                    'semestre_orden' => (int) ($primero->semestre?->orden_global ?? $primero->semestre?->numero ?? 0),
                ];
            })
            ->sortBy(fn (array $fila): string => sprintf('%04d|%04d|%s', $fila['orden'], $fila['semestre_orden'], $fila['grupo']))
            ->values();
    }

    public function exportarEstadisticaExcel()
    {
        if ($this->ciclo_escolar_id === '') {
            return null;
        }

        $cicloId = (int) $this->ciclo_escolar_id;
        $nivelId = (int) $this->nivel->id;
        $generacionId = $this->enteroFiltro($this->generacion_id);
        $gradoId = $this->enteroFiltro($this->grado_id);
        $semestreId = $this->enteroFiltro($this->semestre_id);
        $grupoId = $this->enteroFiltro($this->grupo_id);

        $restriccionHistorial = static function ($query) use (
            $cicloId,
            $nivelId,
            $generacionId,
            $gradoId,
            $semestreId,
            $grupoId,
        ): void {
            $query
                ->where('ciclo_escolar_id', $cicloId)
                ->where('nivel_id', $nivelId)
                ->where('estado', '!=', InscripcionCiclo::ESTADO_ANULADO)
                ->when($generacionId, fn (Builder $q) => $q->where('generacion_id', $generacionId))
                ->when($gradoId, fn (Builder $q) => $q->where('grado_id', $gradoId))
                ->when($semestreId, fn (Builder $q) => $q->where('semestre_id', $semestreId))
                ->when($grupoId, fn (Builder $q) => $q->where('grupo_id', $grupoId));
        };

        $rows = Inscripcion::withTrashed()
            ->with([
                'ciclosEscolaresHistorial' => function ($query) use ($restriccionHistorial): void {
                    $restriccionHistorial($query);
                    $query->with(['generacion', 'grado', 'semestre', 'grupo.asignacionGrupo']);
                },
            ])
            ->whereHas('ciclosEscolaresHistorial', $restriccionHistorial)
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();

        $ciclo = $this->cicloSeleccionado?->nombre ?? (string) $cicloId;
        $cicloArchivo = str_replace(['/', '\\', ' '], '-', $ciclo);

        return Excel::download(
            new MatriculaExport($rows, $this->nivel->nombre, $this->slug_nivel === 'bachillerato'),
            'padron_' . $this->slug_nivel . '_' . $cicloArchivo . '_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    private function cargarFiltrosDependientes(): void
    {
        $this->cargarGeneraciones();
        $this->cargarGrados();
        $this->cargarSemestres();
        $this->cargarGrupos();
    }

    private function cargarGeneraciones(): void
    {
        if ($this->ciclo_escolar_id === '') {
            $this->generaciones = collect();
            return;
        }

        $ids = $this->historialBaseQuery()
            ->whereNotNull('generacion_id')
            ->distinct()
            ->pluck('generacion_id');

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->whereIn('id', $ids)
            ->orderByDesc('anio_ingreso')
            ->orderByDesc('anio_egreso')
            ->orderByDesc('id')
            ->get();

        if ($this->generacion_id !== '' && ! $this->generaciones->contains('id', (int) $this->generacion_id)) {
            $this->generacion_id = '';
        }
    }

    private function cargarGrados(): void
    {
        if ($this->ciclo_escolar_id === '') {
            $this->grados = collect();
            return;
        }

        $ids = $this->historialBaseQuery()
            ->when($this->generacion_id !== '', fn (Builder $query) => $query->where('generacion_id', (int) $this->generacion_id))
            ->whereNotNull('grado_id')
            ->distinct()
            ->pluck('grado_id');

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->whereIn('id', $ids)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        if ($this->grado_id !== '' && ! $this->grados->contains('id', (int) $this->grado_id)) {
            $this->grado_id = '';
        }
    }

    private function cargarSemestres(): void
    {
        if ($this->slug_nivel !== 'bachillerato' || $this->ciclo_escolar_id === '') {
            $this->semestres = collect();
            $this->semestre_id = '';
            return;
        }

        $ids = $this->historialBaseQuery()
            ->when($this->generacion_id !== '', fn (Builder $query) => $query->where('generacion_id', (int) $this->generacion_id))
            ->when($this->grado_id !== '', fn (Builder $query) => $query->where('grado_id', (int) $this->grado_id))
            ->whereNotNull('semestre_id')
            ->distinct()
            ->pluck('semestre_id');

        $this->semestres = Semestre::query()
            ->whereIn('id', $ids)
            ->orderByRaw('COALESCE(orden_global, 255)')
            ->orderBy('numero')
            ->get();

        if ($this->semestre_id !== '' && ! $this->semestres->contains('id', (int) $this->semestre_id)) {
            $this->semestre_id = '';
        }
    }

    private function cargarGrupos(): void
    {
        if ($this->ciclo_escolar_id === '') {
            $this->grupos = collect();
            return;
        }

        $ids = $this->historialBaseQuery()
            ->when($this->generacion_id !== '', fn (Builder $query) => $query->where('generacion_id', (int) $this->generacion_id))
            ->when($this->grado_id !== '', fn (Builder $query) => $query->where('grado_id', (int) $this->grado_id))
            ->when($this->semestre_id !== '', fn (Builder $query) => $query->where('semestre_id', (int) $this->semestre_id))
            ->whereNotNull('grupo_id')
            ->distinct()
            ->pluck('grupo_id');

        $this->grupos = Grupo::withTrashed()
            ->with(['asignacionGrupo:id,nombre', 'grado:id,nombre,orden', 'semestre:id,numero,orden_global'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Grupo $grupo): string => sprintf(
                '%04d|%04d|%s',
                (int) ($grupo->grado?->orden ?? 999),
                (int) ($grupo->semestre?->orden_global ?? $grupo->semestre?->numero ?? 0),
                (string) ($grupo->asignacionGrupo?->nombre ?? '')
            ))
            ->values();

        if ($this->grupo_id !== '' && ! $this->grupos->contains('id', (int) $this->grupo_id)) {
            $this->grupo_id = '';
        }
    }

    private function historialBaseQuery(): Builder
    {
        $query = InscripcionCiclo::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('estado', '!=', InscripcionCiclo::ESTADO_ANULADO);

        if ($this->ciclo_escolar_id === '') {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('ciclo_escolar_id', (int) $this->ciclo_escolar_id);
    }

    private function alumnosQuery(): Builder
    {
        return $this->historialBaseQuery()
            ->with([
                'inscripcion',
                'generacion',
                'grado',
                'semestre',
                'grupo' => fn ($query) => $query->withTrashed()->with('asignacionGrupo'),
            ])
            ->when($this->generacion_id !== '', fn (Builder $query) => $query->where('generacion_id', (int) $this->generacion_id))
            ->when($this->grado_id !== '', fn (Builder $query) => $query->where('grado_id', (int) $this->grado_id))
            ->when($this->semestre_id !== '', fn (Builder $query) => $query->where('semestre_id', (int) $this->semestre_id))
            ->when($this->grupo_id !== '', fn (Builder $query) => $query->where('grupo_id', (int) $this->grupo_id))
            ->orderByDesc('id');
    }

    private function registrosFiltrados(): Collection
    {
        return $this->alumnosQuery()
            ->get()
            ->unique('inscripcion_id')
            ->values();
    }

    private function esActivoEnCiclo(InscripcionCiclo $registro): bool
    {
        return $registro->estado === InscripcionCiclo::ESTADO_EN_CURSO
            && in_array((string) $registro->estatus_actual_ciclo, ['activo', 'reingreso', 'no_promovido'], true);
    }

    private function estadoRegistro(InscripcionCiclo $registro): string
    {
        return (string) (
            $registro->resultado_final
            ?: $registro->estatus_actual_ciclo
            ?: ($registro->estado === InscripcionCiclo::ESTADO_EN_CURSO ? 'activo' : 'inactivo')
        );
    }

    private function categoriaRegistro(InscripcionCiclo $registro): string
    {
        $estado = $this->estadoRegistro($registro);

        return match ($estado) {
            'baja_temporal', 'baja_temporal_al_cierre', 'baja_definitiva' => 'baja',
            'traslado', 'trasladado' => 'traslado',
            'suspendido' => 'suspendido',
            'egresado' => 'egresado',
            'inactivo', 'no_reinscrito', 'pendiente_reinscripcion', 'no_iniciado' => 'inactivo',
            default => $this->esActivoEnCiclo($registro) ? 'activo' : 'inactivo',
        };
    }

    private function cicloValido(mixed $value): bool
    {
        $id = (int) $value;

        return $id > 0 && $this->ciclosEscolares->contains('id', $id);
    }

    private function enteroFiltro(string $value): ?int
    {
        return $value !== '' ? (int) $value : null;
    }

    public function render()
    {
        return view('livewire.accion.generales');
    }
}
