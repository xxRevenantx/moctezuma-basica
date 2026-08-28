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
use App\Services\SincronizadorOrdenCargaAcademicaService;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

trait GestionaCopiaCargaAcademica
{
    public function copiarDesdeCiclo(): void
    {
        $this->autorizarAdministracion();

        $this->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'ciclo_origen_id' => ['required', 'integer', 'exists:ciclo_escolares,id', Rule::notIn([(int) $this->ciclo_escolar_id])],
        ]);

        $destino = CicloEscolar::query()->findOrFail($this->ciclo_escolar_id);
        $origen = CicloEscolar::query()->findOrFail($this->ciclo_origen_id);

        if ((int) $origen->inicio_anio >= (int) $destino->inicio_anio) {
            $this->addError('ciclo_origen_id', 'El ciclo origen debe ser anterior al ciclo de trabajo.');
            return;
        }

        $totalOrigen = AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_origen_id)
            ->where('nivel_id', $this->nivel->id)
            ->confirmadas()
            ->count();

        if ($totalOrigen === 0) {
            $this->addError('ciclo_origen_id', 'El ciclo seleccionado no tiene cargas confirmadas para este nivel.');
            return;
        }

        $creadas = 0;
        $omitidas = 0;
        $horariosCopiados = 0;
        $conflictosOrden = 0;

        DB::transaction(function () use (&$creadas, &$omitidas, &$horariosCopiados, &$conflictosOrden) {
            $origenes = AsignacionMateriaModel::query()
                ->with(['grupo', 'materia', 'horarios' => fn($q) => $q->where('ciclo_escolar_id', $this->ciclo_origen_id)])
                ->where('ciclo_escolar_id', $this->ciclo_origen_id)
                ->where('nivel_id', $this->nivel->id)
                ->confirmadas()
                ->get();

            foreach ($origenes as $origen) {
                if (! $origen->materia) {
                    $omitidas++;
                    continue;
                }

                try {
                    app(SincronizadorOrdenCargaAcademicaService::class)
                        ->asegurarContextoSinDuplicados($origen->materia);
                } catch (DomainException) {
                    $omitidas++;
                    $conflictosOrden++;
                    continue;
                }

                $grupoDestino = $this->resolverGrupoDestino($origen);

                if (!$grupoDestino) {
                    $omitidas++;
                    continue;
                }

                $existe = AsignacionMateriaModel::query()
                    ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                    ->where('grupo_id', $grupoDestino->id)
                    ->where('materia_id', $origen->materia_id)
                    ->first();

                if ($existe) {
                    $omitidas++;
                    continue;
                }

                $nueva = AsignacionMateriaModel::query()->create([
                    'materia_id' => $origen->materia_id,
                    'grupo_id' => $grupoDestino->id,
                    'profesor_id' => $this->copiar_profesores ? $origen->profesor_id : null,
                    'ciclo_escolar_id' => $this->ciclo_escolar_id,
                    'nivel_id' => $grupoDestino->nivel_id,
                    'grado_id' => $grupoDestino->grado_id,
                    'generacion_id' => $grupoDestino->generacion_id,
                    'semestre_id' => $grupoDestino->semestre_id,
                    'estado' => AsignacionMateriaModel::ESTADO_BORRADOR,
                    'asignacion_origen_id' => $origen->id,
                ]);
                $creadas++;

                if (!$this->copiar_horarios) {
                    continue;
                }

                foreach ($origen->horarios as $horarioOrigen) {
                    $ocupada = Horario::query()
                        ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                        ->where('grupo_id', $grupoDestino->id)
                        ->where('dia_id', $horarioOrigen->dia_id)
                        ->where('hora_id', $horarioOrigen->hora_id)
                        ->exists();

                    if ($ocupada) {
                        continue;
                    }

                    Horario::query()->create([
                        'nivel_id' => $grupoDestino->nivel_id,
                        'grado_id' => $grupoDestino->grado_id,
                        'generacion_id' => $grupoDestino->generacion_id,
                        'semestre_id' => $grupoDestino->semestre_id,
                        'grupo_id' => $grupoDestino->id,
                        'hora_id' => $horarioOrigen->hora_id,
                        'dia_id' => $horarioOrigen->dia_id,
                        'asignacion_materia_id' => $nueva->id,
                        'taller_sesion_id' => null,
                        'ciclo_escolar_id' => $this->ciclo_escolar_id,
                    ]);
                    $horariosCopiados++;
                }
            }
        });

        if ($creadas > 0) {
            $this->filtro_estado = AsignacionMateriaModel::ESTADO_BORRADOR;
            $this->limpiarSeleccionTabla();
            $this->resetPage('materiasPage');
        }

        $detalleOrden = $conflictosOrden > 0
            ? " {$conflictosOrden} carga(s) se omitieron porque el catálogo de Materias tiene órdenes duplicados."
            : '';

        $this->dispatch('swal', [
            'title' => 'Preparación del ciclo terminada',
            'text' => "Nuevas en borrador: {$creadas}. Omitidas: {$omitidas}. Horarios copiados para revisión: {$horariosCopiados}.{$detalleOrden} Confirma las cargas cuando estén listas.",
            'icon' => $conflictosOrden > 0 ? 'warning' : 'success',
            'position' => 'top-end',
        ]);
    }

    private function resolverGrupoDestino(AsignacionMateriaModel $origen): ?Grupo
    {
        $origen->loadMissing('grupo');
        $grupoOrigen = $origen->grupo;

        if (! $grupoOrigen || ! $this->ciclo_escolar_id) {
            return null;
        }

        $idsVigentes = Inscripcion::query()
            ->visiblesEnListas()
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $grupoOrigen->grado_id)
            ->whereNotNull('grupo_id')
            ->when($grupoOrigen->semestre_id, fn($q) => $q->where('semestre_id', $grupoOrigen->semestre_id))
            ->pluck('grupo_id')
            ->unique();

        $base = Grupo::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $grupoOrigen->grado_id)
            ->where('asignacion_grupo_id', $grupoOrigen->asignacion_grupo_id)
            ->where('estado', 'activo')
            ->when(
                $grupoOrigen->semestre_id,
                fn($q) => $q->where('semestre_id', $grupoOrigen->semestre_id),
                fn($q) => $q->whereNull('semestre_id')
            );

        // Si la matrícula vigente ya apunta a un grupo del ciclo destino, ese
        // contexto tiene prioridad. Nunca se reutiliza el grupo del ciclo origen.
        if ($idsVigentes->isNotEmpty()) {
            $grupoVigente = (clone $base)
                ->whereIn('id', $idsVigentes)
                ->orderByDesc('generacion_id')
                ->first();

            if ($grupoVigente) {
                return $grupoVigente;
            }
        }

        return $base
            ->orderByDesc('generacion_id')
            ->first();
    }

}
