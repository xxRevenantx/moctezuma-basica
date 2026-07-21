<?php

namespace App\Livewire\Admin;

use App\Models\AsignacionMateria;
use App\Models\CicloEscolar;
use App\Models\Grupo;
use App\Services\SystemAuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class RevisionCiclosAcademicos extends Component
{
    use WithPagination;

    /** @var array<int,int|string|null> */
    public array $cicloAsignacion = [];

    /** @var array<int,string> */
    public array $motivoAsignacion = [];

    /** @var array<int,int|string|null> */
    public array $cicloGrupo = [];

    /** @var array<int,string> */
    public array $motivoGrupo = [];

    public bool $mostrarResueltas = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }

    public function justificarAsignacion(int $asignacionId, SystemAuditService $audit): void
    {
        $motivo = trim((string) ($this->motivoAsignacion[$asignacionId] ?? ''));

        if (mb_strlen($motivo) < 10) {
            $this->addError("motivoAsignacion.{$asignacionId}", 'Escribe una justificación de al menos 10 caracteres.');
            return;
        }

        $asignacion = AsignacionMateria::query()->with('grupo')->findOrFail($asignacionId);
        $asignacion->forceFill([
            'revision_ciclo_estado' => 'justificada',
            'revision_ciclo_observacion' => $motivo,
            'revision_ciclo_at' => now(),
            'revision_ciclo_por' => auth()->id(),
        ])->save();

        $audit->record('assignment_cycle_justified', 'academico', [
            'asignacion_materia_id' => $asignacion->id,
            'ciclo_asignacion_id' => $asignacion->ciclo_escolar_id,
            'ciclo_grupo_id' => $asignacion->grupo?->ciclo_escolar_id,
            'motivo' => $motivo,
        ]);

        unset($this->motivoAsignacion[$asignacionId]);
        $this->dispatch('swal', icon: 'success', title: 'Diferencia justificada', text: 'No se cambiaron datos históricos.');
    }

    public function aplicarCicloDelGrupo(int $asignacionId, SystemAuditService $audit): void
    {
        $motivo = trim((string) ($this->motivoAsignacion[$asignacionId] ?? ''));

        if (mb_strlen($motivo) < 10) {
            $this->addError("motivoAsignacion.{$asignacionId}", 'Describe por qué la carga debe adoptar el ciclo del grupo.');
            return;
        }

        DB::transaction(function () use ($asignacionId, $motivo, $audit): void {
            $asignacion = AsignacionMateria::query()
                ->with('grupo')
                ->withCount(['calificaciones', 'horarios', 'bitacoraCalificaciones'])
                ->lockForUpdate()
                ->findOrFail($asignacionId);

            abort_unless($asignacion->grupo?->ciclo_escolar_id, 422, 'El grupo todavía no tiene un ciclo definido.');

            if ($asignacion->calificaciones_count || $asignacion->horarios_count || $asignacion->bitacora_calificaciones_count) {
                $this->addError(
                    "motivoAsignacion.{$asignacionId}",
                    'La asignación ya tiene historial académico. No se modificó; justifícala o corrige primero sus horarios y calificaciones desde los módulos correspondientes.'
                );
                return;
            }

            $anterior = (int) $asignacion->ciclo_escolar_id;
            $nuevo = (int) $asignacion->grupo->ciclo_escolar_id;

            $asignacion->forceFill([
                'ciclo_escolar_id' => $nuevo,
                'revision_ciclo_estado' => 'corregida',
                'revision_ciclo_observacion' => $motivo,
                'revision_ciclo_at' => now(),
                'revision_ciclo_por' => auth()->id(),
            ])->save();

            $audit->record('assignment_cycle_corrected', 'academico', [
                'asignacion_materia_id' => $asignacion->id,
                'ciclo_anterior_id' => $anterior,
                'ciclo_nuevo_id' => $nuevo,
                'motivo' => $motivo,
            ]);
        });

        if (!$this->getErrorBag()->has("motivoAsignacion.{$asignacionId}")) {
            unset($this->motivoAsignacion[$asignacionId]);
            $this->dispatch('swal', icon: 'success', title: 'Ciclo corregido', text: 'La carga ahora utiliza el ciclo de su grupo.');
        }
    }

    public function actualizarGrupoArchivado(int $grupoId, bool $reactivar, SystemAuditService $audit): void
    {
        $cicloId = (int) ($this->cicloGrupo[$grupoId] ?? 0);
        $motivo = trim((string) ($this->motivoGrupo[$grupoId] ?? ''));

        if (!$cicloId) {
            $this->addError("cicloGrupo.{$grupoId}", 'Selecciona el ciclo correcto.');
            return;
        }

        if (mb_strlen($motivo) < 10) {
            $this->addError("motivoGrupo.{$grupoId}", 'Escribe el motivo de la corrección.');
            return;
        }

        DB::transaction(function () use ($grupoId, $cicloId, $motivo, $reactivar, $audit): void {
            $grupo = Grupo::withTrashed()->lockForUpdate()->findOrFail($grupoId);
            $anterior = $grupo->only(['ciclo_escolar_id', 'estado', 'archivado_at', 'motivo_archivo']);

            $grupo->forceFill([
                'ciclo_escolar_id' => $cicloId,
                'estado' => $reactivar ? 'activo' : 'archivado',
                'archivado_at' => $reactivar ? null : ($grupo->archivado_at ?: now()),
                'archivado_por' => $reactivar ? null : auth()->id(),
                'motivo_archivo' => $reactivar ? null : $motivo,
            ])->save();

            $audit->record('archived_group_reviewed', 'grupos', [
                'grupo_id' => $grupo->id,
                'before' => $anterior,
                'after' => $grupo->fresh()->only(['ciclo_escolar_id', 'estado', 'archivado_at', 'motivo_archivo']),
                'motivo' => $motivo,
            ]);
        });

        unset($this->cicloGrupo[$grupoId], $this->motivoGrupo[$grupoId]);
        $this->dispatch('swal', icon: 'success', title: $reactivar ? 'Grupo corregido y reactivado' : 'Grupo archivado actualizado');
    }

    public function render()
    {
        $asignaciones = AsignacionMateria::query()
            ->with(['materia:id,nombre', 'grupo.cicloEscolar', 'grupo.nivel', 'grupo.grado', 'grupo.generacion', 'cicloEscolar'])
            ->withCount(['calificaciones', 'horarios', 'bitacoraCalificaciones'])
            ->whereHas('grupo', fn (Builder $query) => $query->whereNotNull('ciclo_escolar_id'))
            ->where(function (Builder $query): void {
                $query->whereHas('grupo', function (Builder $grupo): void {
                    $grupo->whereColumn('grupos.ciclo_escolar_id', '!=', 'asignacion_materias.ciclo_escolar_id');
                })->orWhere('revision_ciclo_estado', 'pendiente');
            })
            ->when(!$this->mostrarResueltas, fn (Builder $query) => $query->whereNotIn('revision_ciclo_estado', ['justificada', 'corregida']))
            ->orderBy('id')
            ->paginate(15);

        $gruposArchivados = Grupo::withTrashed()
            ->with(['cicloEscolar', 'nivel', 'grado', 'generacion', 'asignacionGrupo'])
            ->withCount(['inscripciones', 'asignacionMaterias', 'horarios', 'calificaciones', 'personaNivelDetalles'])
            ->where(function (Builder $query): void {
                $query->where('estado', 'archivado')
                    ->orWhereNotNull('archivado_at')
                    ->orWhereNull('ciclo_escolar_id');
            })
            ->orderByDesc('id')
            ->get();

        return view('livewire.admin.revision-ciclos-academicos', [
            'asignaciones' => $asignaciones,
            'gruposArchivados' => $gruposArchivados,
            'ciclos' => CicloEscolar::query()->orderByDesc('inicio_anio')->get(),
        ]);
    }
}
