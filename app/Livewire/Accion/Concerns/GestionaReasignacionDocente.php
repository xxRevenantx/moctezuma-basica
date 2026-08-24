<?php

namespace App\Livewire\Accion\Concerns;

use App\Models\AsignacionMateria as AsignacionMateriaModel;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Materia;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\PersonaNivel;
use App\Models\PersonaNivelDetalle;
use App\Models\ReasignacionDocenteLote;
use App\Models\Semestre;
use App\Models\Inscripcion;
use App\Services\CicloNivelGateService;
use App\Services\PlantillaDocenteService;
use App\Services\ReasignacionDocenteMasivaService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

trait GestionaReasignacionDocente
{
    public function getProfesoresOrigenReasignacionProperty(): Collection
    {
        if (! $this->ciclo_escolar_id) {
            return collect();
        }

        $estados = [AsignacionMateriaModel::ESTADO_BORRADOR, AsignacionMateriaModel::ESTADO_ACTIVA];

        if ($this->reasignacion_incluir_cerradas) {
            $estados[] = AsignacionMateriaModel::ESTADO_CERRADA;
        }

        if ($this->reasignacion_incluir_archivadas) {
            $estados[] = AsignacionMateriaModel::ESTADO_ARCHIVADA;
        }

        $base = $this->consultaAsignacionesBase()
            ->whereHas('materia', fn (Builder $materia) => $materia->where('receso', false))
            ->whereIn('estado', array_values(array_unique($estados)));

        $conteos = (clone $base)
            ->selectRaw('profesor_id, COUNT(*) as total')
            ->groupBy('profesor_id')
            ->pluck('total', 'profesor_id');

        $personas = Persona::query()
            ->withTrashed()
            ->whereIn('id', $conteos->keys()->filter())
            ->get()
            ->map(fn (Persona $persona) => [
                'valor' => (string) $persona->id,
                'nombre' => trim(($persona->titulo ?? '') . ' ' . ($persona->nombre ?? '') . ' '
                    . ($persona->apellido_paterno ?? '') . ' ' . ($persona->apellido_materno ?? ''))
                    . ($persona->trashed() ? ' · registro eliminado' : ''),
                'total' => (int) ($conteos[$persona->id] ?? 0),
            ]);

        if ((int) ($conteos[''] ?? $conteos[null] ?? 0) > 0 || (clone $base)->whereNull('profesor_id')->exists()) {
            $personas->push([
                'valor' => 'sin_docente',
                'nombre' => 'Sin docente asignado',
                'total' => (clone $base)->whereNull('profesor_id')->count(),
            ]);
        }

        return $personas->sortBy(fn (array $item) => mb_strtolower($item['nombre']))->values();
    }

    private function consultaOpcionesReasignacion(): Builder
    {
        return $this->consultaAsignacionesBase()
            ->whereHas('materia', fn (Builder $materia) => $materia->where('receso', false));
    }

    public function getReasignacionGeneracionesProperty(): Collection
    {
        $ids = $this->consultaOpcionesReasignacion()
            ->whereNotNull('generacion_id')
            ->distinct()
            ->pluck('generacion_id');

        return Generacion::query()
            ->whereIn('id', $ids)
            ->orderByDesc('anio_ingreso')
            ->orderByDesc('anio_egreso')
            ->get();
    }

    public function getReasignacionGradosProperty(): Collection
    {
        $ids = $this->consultaOpcionesReasignacion()
            ->when(filled($this->reasignacion_generacion), fn (Builder $q) => $q->where('generacion_id', (int) $this->reasignacion_generacion))
            ->whereNotNull('grado_id')
            ->distinct()
            ->pluck('grado_id');

        return Grado::query()->whereIn('id', $ids)->orderBy('orden')->orderBy('nombre')->get();
    }

    public function getReasignacionSemestresProperty(): Collection
    {
        if (! $this->esBachillerato) {
            return collect();
        }

        $ids = $this->consultaOpcionesReasignacion()
            ->when(filled($this->reasignacion_generacion), fn (Builder $q) => $q->where('generacion_id', (int) $this->reasignacion_generacion))
            ->when(filled($this->reasignacion_grado), fn (Builder $q) => $q->where('grado_id', (int) $this->reasignacion_grado))
            ->whereNotNull('semestre_id')
            ->distinct()
            ->pluck('semestre_id');

        return Semestre::query()->whereIn('id', $ids)->orderBy('orden_global')->orderBy('numero')->get();
    }

    public function getReasignacionGruposProperty(): Collection
    {
        $ids = $this->consultaOpcionesReasignacion()
            ->when(filled($this->reasignacion_generacion), fn (Builder $q) => $q->where('generacion_id', (int) $this->reasignacion_generacion))
            ->when(filled($this->reasignacion_grado), fn (Builder $q) => $q->where('grado_id', (int) $this->reasignacion_grado))
            ->when(filled($this->reasignacion_semestre), fn (Builder $q) => $q->where('semestre_id', (int) $this->reasignacion_semestre))
            ->whereNotNull('grupo_id')
            ->distinct()
            ->pluck('grupo_id');

        return Grupo::query()
            ->with(['asignacionGrupo', 'grado', 'generacion', 'semestre'])
            ->whereIn('id', $ids)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->get()
            ->sortBy(fn (Grupo $grupo) => sprintf(
                '%03d|%03d|%s',
                (int) ($grupo->grado?->orden ?? 999),
                (int) ($grupo->semestre?->orden_global ?? 999),
                mb_strtolower((string) ($grupo->asignacionGrupo?->nombre ?? '')),
            ))
            ->values();
    }

    private function consultaCandidatosReasignacion(): Builder
    {
        $estados = [AsignacionMateriaModel::ESTADO_BORRADOR, AsignacionMateriaModel::ESTADO_ACTIVA];

        if ($this->reasignacion_incluir_cerradas) {
            $estados[] = AsignacionMateriaModel::ESTADO_CERRADA;
        }

        if ($this->reasignacion_incluir_archivadas) {
            $estados[] = AsignacionMateriaModel::ESTADO_ARCHIVADA;
        }

        $query = $this->consultaAsignacionesBase()
            ->with([
                'materia',
                'profesor',
                'grupo.grado',
                'grupo.generacion',
                'grupo.semestre',
                'grupo.asignacionGrupo',
            ])
            ->withCount(['horarios', 'calificaciones', 'bitacoraCalificaciones'])
            ->whereHas('materia', fn (Builder $materia) => $materia->where('receso', false))
            ->whereIn('estado', array_values(array_unique($estados)));

        if ($this->reasignacion_modo === 'seleccion') {
            $ids = collect($this->reasignacion_ids_base)->map(fn ($id) => (int) $id)->filter()->unique();
            $query->when($ids->isNotEmpty(), fn (Builder $q) => $q->whereIn('id', $ids))
                ->when($ids->isEmpty(), fn (Builder $q) => $q->whereRaw('1 = 0'));
        } else {
            $query->when(
                $this->reasignacion_origen === 'sin_docente',
                fn (Builder $q) => $q->whereNull('profesor_id'),
                fn (Builder $q) => filled($this->reasignacion_origen)
                    ? $q->where('profesor_id', (int) $this->reasignacion_origen)
                    : $q->whereRaw('1 = 0')
            );
        }

        return $query
            ->when(filled($this->reasignacion_generacion), fn (Builder $q) => $q->where('generacion_id', (int) $this->reasignacion_generacion))
            ->when(filled($this->reasignacion_estado), fn (Builder $q) => $q->where('estado', $this->reasignacion_estado))
            ->when(filled($this->reasignacion_grado), fn (Builder $q) => $q->where('grado_id', (int) $this->reasignacion_grado))
            ->when(filled($this->reasignacion_semestre), fn (Builder $q) => $q->where('semestre_id', (int) $this->reasignacion_semestre))
            ->when(filled($this->reasignacion_grupo), fn (Builder $q) => $q->where('grupo_id', (int) $this->reasignacion_grupo))
            ->when($this->reasignacion_horario === 'con', fn (Builder $q) => $q->whereHas('horarios', fn (Builder $h) => $h->where('ciclo_escolar_id', $this->ciclo_escolar_id)))
            ->when($this->reasignacion_horario === 'sin', fn (Builder $q) => $q->whereDoesntHave('horarios', fn (Builder $h) => $h->where('ciclo_escolar_id', $this->ciclo_escolar_id)))
            ->when(trim($this->reasignacion_buscar) !== '', function (Builder $q): void {
                $buscar = '%' . trim($this->reasignacion_buscar) . '%';
                $q->where(function (Builder $sub) use ($buscar): void {
                    $sub->whereHas('materia', fn (Builder $m) => $m
                        ->where('materia', 'like', $buscar)
                        ->orWhere('clave', 'like', $buscar))
                        ->orWhereHas('grupo.asignacionGrupo', fn (Builder $g) => $g->where('nombre', 'like', $buscar))
                        ->orWhereHas('grupo.grado', fn (Builder $g) => $g->where('nombre', 'like', $buscar));
                });
            });
    }

    public function getCandidatosReasignacionProperty(): Collection
    {
        return $this->consultaCandidatosReasignacion()
            ->orderBy('grado_id')
            ->orderByRaw('CASE WHEN semestre_id IS NULL THEN 999 ELSE semestre_id END')
            ->orderBy('grupo_id')
            ->orderBy('orden')
            ->limit(250)
            ->get();
    }

    public function getTotalCandidatosReasignacionProperty(): int
    {
        return $this->consultaCandidatosReasignacion()->count();
    }

    public function getHistorialReasignacionesProperty(): Collection
    {
        if (! $this->ciclo_escolar_id) {
            return collect();
        }

        return ReasignacionDocenteLote::query()
            ->with(['profesorOrigen', 'profesorDestino', 'usuarioAplicacion', 'usuarioReversion'])
            ->withCount('detalles')
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->latest('aplicado_at')
            ->latest('id')
            ->limit(15)
            ->get();
    }

    public function abrirReasignacionMasiva(): void
    {
        $this->autorizarAdministracion();
        $seleccion = collect($this->seleccionados)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();

        $this->resetEstadoReasignacion();
        $this->reasignacion_modo = $seleccion !== [] ? 'seleccion' : 'profesor';
        $this->reasignacion_ids_base = $seleccion;
        $this->reasignacion_seleccionados = $seleccion;
        $this->modalReasignacionAbierto = true;
    }

    public function cerrarReasignacionMasiva(): void
    {
        $this->modalReasignacionAbierto = false;
        $this->resetEstadoReasignacion();
    }

    private function resetEstadoReasignacion(): void
    {
        $this->reset([
            'reasignacion_paso',
            'reasignacion_modo',
            'reasignacion_origen',
            'reasignacion_destino_id',
            'reasignacion_ids_base',
            'reasignacion_seleccionados',
            'reasignacion_buscar',
            'reasignacion_generacion',
            'reasignacion_estado',
            'reasignacion_grado',
            'reasignacion_semestre',
            'reasignacion_grupo',
            'reasignacion_horario',
            'reasignacion_incluir_cerradas',
            'reasignacion_incluir_archivadas',
            'reasignacion_autorizar_conflictos',
            'reasignacion_motivo_conflictos',
            'reasignacion_preview',
        ]);
        $this->reasignacion_paso = 'seleccion';
        $this->reasignacion_modo = 'seleccion';
        $this->resetValidation();
    }

    public function updatedReasignacionModo(): void
    {
        $this->reasignacion_preview = [];
        $this->reasignacion_paso = 'seleccion';
        $this->reasignacion_origen = '';
        $this->reasignacion_seleccionados = [];

        if ($this->reasignacion_modo === 'seleccion') {
            $this->reasignacion_ids_base = collect($this->seleccionados)
                ->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
            $this->reasignacion_seleccionados = $this->reasignacion_ids_base;
        }
    }

    public function updatedReasignacionOrigen(): void
    {
        $this->reasignacion_seleccionados = [];
        $this->reasignacion_preview = [];
        $this->reasignacion_paso = 'seleccion';
    }

    public function updatedReasignacionBuscar(): void
    {
        $this->depurarSeleccionReasignacion();
    }

    public function updatedReasignacionGeneracion(): void
    {
        $this->reset(['reasignacion_grado', 'reasignacion_semestre', 'reasignacion_grupo']);
        $this->depurarSeleccionReasignacion();
    }

    public function updatedReasignacionEstado(): void
    {
        $this->depurarSeleccionReasignacion();
    }

    public function updatedReasignacionGrado(): void
    {
        $this->reset(['reasignacion_semestre', 'reasignacion_grupo']);
        $this->depurarSeleccionReasignacion();
    }

    public function updatedReasignacionSemestre(): void
    {
        $this->reset(['reasignacion_grupo']);
        $this->depurarSeleccionReasignacion();
    }

    public function updatedReasignacionGrupo(): void
    {
        $this->depurarSeleccionReasignacion();
    }

    public function updatedReasignacionHorario(): void
    {
        $this->depurarSeleccionReasignacion();
    }

    public function updatedReasignacionIncluirCerradas(bool $incluir): void
    {
        if (! $incluir && $this->reasignacion_estado === AsignacionMateriaModel::ESTADO_CERRADA) {
            $this->reasignacion_estado = '';
        }

        $this->depurarSeleccionReasignacion();
    }

    public function updatedReasignacionIncluirArchivadas(bool $incluir): void
    {
        if (! $incluir && $this->reasignacion_estado === AsignacionMateriaModel::ESTADO_ARCHIVADA) {
            $this->reasignacion_estado = '';
        }

        $this->depurarSeleccionReasignacion();
    }

    private function depurarSeleccionReasignacion(): void
    {
        $permitidos = $this->consultaCandidatosReasignacion()
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $this->reasignacion_seleccionados = collect($this->reasignacion_seleccionados)
            ->map(fn ($id) => (int) $id)
            ->intersect($permitidos)
            ->values()
            ->all();
        $this->reasignacion_preview = [];
        $this->reasignacion_paso = 'seleccion';
    }

    public function seleccionarTodosCandidatosReasignacion(): void
    {
        $this->reasignacion_seleccionados = $this->consultaCandidatosReasignacion()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function limpiarSeleccionReasignacion(): void
    {
        $this->reasignacion_seleccionados = [];
        $this->reasignacion_preview = [];
    }

    public function previsualizarReasignacion(): void
    {
        $this->autorizarAdministracion();

        $this->validate([
            'reasignacion_destino_id' => ['required', 'integer', 'exists:personas,id'],
            'reasignacion_seleccionados' => ['required', 'array', 'min:1'],
            'reasignacion_seleccionados.*' => ['integer', 'exists:asignacion_materias,id'],
        ], [], [
            'reasignacion_destino_id' => 'profesor destino',
            'reasignacion_seleccionados' => 'materias seleccionadas',
        ]);

        if ($this->reasignacion_modo === 'profesor'
            && ctype_digit($this->reasignacion_origen)
            && (int) $this->reasignacion_origen === (int) $this->reasignacion_destino_id) {
            $this->addError('reasignacion_destino_id', 'El profesor destino debe ser diferente al profesor actual.');
            return;
        }

        $this->reasignacion_preview = app(ReasignacionDocenteMasivaService::class)->previsualizar(
            asignacionIds: $this->reasignacion_seleccionados,
            profesorDestinoId: (int) $this->reasignacion_destino_id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            nivelId: (int) $this->nivel->id,
            incluirCerradas: $this->reasignacion_incluir_cerradas,
            incluirArchivadas: $this->reasignacion_incluir_archivadas,
        );
        $this->reasignacion_paso = 'preview';
        $this->reasignacion_autorizar_conflictos = false;
        $this->reasignacion_motivo_conflictos = '';
    }

    public function volverSeleccionReasignacion(): void
    {
        $this->reasignacion_paso = 'seleccion';
        $this->reasignacion_preview = [];
        $this->resetValidation(['reasignacion_autorizar_conflictos', 'reasignacion_motivo_conflictos']);
    }

    public function aplicarReasignacionMasiva(): void
    {
        $this->autorizarAdministracion();

        $lote = app(ReasignacionDocenteMasivaService::class)->aplicar(
            asignacionIds: $this->reasignacion_seleccionados,
            profesorDestinoId: (int) $this->reasignacion_destino_id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            nivelId: (int) $this->nivel->id,
            modo: $this->reasignacion_modo,
            profesorOrigenId: $this->reasignacion_modo === 'profesor' && ctype_digit($this->reasignacion_origen)
                ? (int) $this->reasignacion_origen
                : null,
            incluirCerradas: $this->reasignacion_incluir_cerradas,
            incluirArchivadas: $this->reasignacion_incluir_archivadas,
            autorizarConflictos: $this->reasignacion_autorizar_conflictos,
            motivoConflictos: $this->reasignacion_motivo_conflictos,
            usuarioId: auth()->id(),
        );

        $total = $lote->total_asignaciones;
        $this->modalReasignacionAbierto = false;
        $this->resetEstadoReasignacion();
        $this->limpiarSeleccionTabla();
        $this->resetPage('materiasPage');

        $this->dispatch('swal', [
            'title' => 'Reasignación masiva aplicada',
            'text' => "Se actualizaron {$total} carga(s), sus horarios operativos y las propuestas editables.",
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function abrirHistorialReasignaciones(): void
    {
        $this->autorizarAdministracion();
        $this->modalHistorialReasignacionesAbierto = true;
    }

    public function cerrarHistorialReasignaciones(): void
    {
        $this->modalHistorialReasignacionesAbierto = false;
    }

    public function revertirReasignacion(int $loteId): void
    {
        $this->autorizarAdministracion();

        $lote = ReasignacionDocenteLote::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($loteId);

        $resultado = app(ReasignacionDocenteMasivaService::class)->revertir($lote, auth()->id());

        $this->dispatch('swal', [
            'title' => $resultado['omitidas'] > 0 ? 'Reversión parcial' : 'Reasignación revertida',
            'text' => "Restauradas: {$resultado['revertidas']}. Omitidas por cambios posteriores: {$resultado['omitidas']}.",
            'icon' => $resultado['omitidas'] > 0 ? 'warning' : 'success',
            'position' => 'top-end',
        ]);
    }

}
