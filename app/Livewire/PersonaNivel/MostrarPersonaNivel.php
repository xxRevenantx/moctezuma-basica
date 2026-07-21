<?php

namespace App\Livewire\PersonaNivel;

use App\Exports\PlantillaExport;
use App\Models\CicloEscolar;
use App\Models\Nivel;
use App\Models\PersonaNivelCiclo;
use App\Models\PersonaNivelDetalle;
use App\Models\PlantillaPersonalNivel;
use App\Services\PlantillaPersonalCicloService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class MostrarPersonaNivel extends Component
{
    public string $search = '';
    public ?int $cicloEscolarId = null;
    public ?int $detalleArchivarId = null;
    public string $motivoArchivo = '';

    public function mount(PlantillaPersonalCicloService $service): void
    {
        $this->cicloEscolarId = $service->cicloActual()?->id;
    }

    #[On('ciclo-plantilla-cambiado')]
    public function cambiarCiclo(int $cicloId): void
    {
        $this->cicloEscolarId = $cicloId;
        $this->search = '';
    }

    #[On('refreshPersonaNivelList')]
    #[On('plantilla-personal-actualizada')]
    public function refreshPersonaNivelList(): void
    {
        $this->dispatch('$refresh');
    }

    public function solicitarArchivar(int $id): void
    {
        $this->detalleArchivarId = $id;
        $this->motivoArchivo = '';
        $this->resetValidation(['motivoArchivo']);
        $this->dispatch('abrir-modal-archivar-persona-nivel');
    }

    public function confirmarArchivo(PlantillaPersonalCicloService $service): void
    {
        $this->validate([
            'detalleArchivarId' => ['required', 'integer', 'exists:persona_nivel_detalles,id'],
            'motivoArchivo' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $detalle = PersonaNivelDetalle::query()
            ->with('cicloAsignacion.plantilla')
            ->findOrFail($this->detalleArchivarId);
        $plantilla = $detalle->cicloAsignacion?->plantilla;
        abort_unless($plantilla && (int) $plantilla->ciclo_escolar_id === (int) $this->cicloEscolarId, 422);
        $service->asegurarEditable($plantilla);
        $service->archivarDetalle($detalle, $this->motivoArchivo);
        $service->actualizarDiagnostico($plantilla);

        $this->detalleArchivarId = null;
        $this->motivoArchivo = '';
        $this->dispatch('cerrar-modal-archivar-persona-nivel');
        $this->dispatch('refreshPersonaNivelList');
        $this->dispatch('plantilla-personal-actualizada');
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Asignación archivada; el historial se conserva.']);
    }

    public function ordenarJs(int $nivelId, array $ids): void
    {
        $this->ordenarDetalles($nivelId, null, $ids);
    }

    public function ordenarSecJs(int $nivelId, int $membresiaId, array $ids): void
    {
        $this->ordenarDetalles($nivelId, $membresiaId, $ids);
    }

    private function ordenarDetalles(int $nivelId, ?int $membresiaId, array $ids): void
    {
        $ids = collect($ids)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return;
        }

        $plantilla = app(PlantillaPersonalCicloService::class)->plantilla((int) $this->cicloEscolarId, $nivelId, false);
        abort_unless($plantilla, 422, 'No existe plantilla para este nivel y ciclo.');
        app(PlantillaPersonalCicloService::class)->asegurarEditable($plantilla);

        $query = $this->queryDetalles()->whereHas('cabecera', fn (Builder $q) => $q->where('nivel_id', $nivelId));
        if ($membresiaId) {
            $query->where('persona_nivel_ciclo_id', $membresiaId);
        }
        $validos = $query->whereIn('id', $ids)->pluck('id')->all();
        $set = array_flip($validos);

        DB::transaction(function () use ($ids, $set) {
            $orden = 1;
            foreach ($ids as $id) {
                if (isset($set[$id])) {
                    PersonaNivelDetalle::query()->whereKey($id)->update(['orden' => $orden++]);
                }
            }
        });

        $this->dispatch('$refresh');
    }

    public function ordenarPersonasJs(int $nivelId, array $membresiaIds): void
    {
        $ids = collect($membresiaIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return;
        }

        $plantilla = app(PlantillaPersonalCicloService::class)->plantilla((int) $this->cicloEscolarId, $nivelId, false);
        abort_unless($plantilla, 422, 'No existe plantilla para este nivel y ciclo.');
        app(PlantillaPersonalCicloService::class)->asegurarEditable($plantilla);

        $validos = PersonaNivelCiclo::query()
            ->whereIn('id', $ids)
            ->whereHas('plantilla', fn (Builder $q) => $q
                ->where('ciclo_escolar_id', $this->cicloEscolarId)
                ->where('nivel_id', $nivelId))
            ->pluck('id')->all();
        $set = array_flip($validos);

        DB::transaction(function () use ($ids, $set) {
            $orden = 1;
            foreach ($ids as $id) {
                if (isset($set[$id])) {
                    PersonaNivelCiclo::query()->whereKey($id)->update(['orden' => $orden++]);
                }
            }
        });

        $this->dispatch('$refresh');
    }

    public function exportarPlantilla(int $nivelId)
    {
        $rows = $this->queryDetalles()
            ->with(['cabecera.persona', 'cabecera.nivel', 'grado', 'grupo.asignacionGrupo', 'grupo.generacion', 'personaRole.rolePersona'])
            ->where('estado', PersonaNivelDetalle::ESTADO_ACTIVO)
            ->where('confirmado', true)
            ->whereHas('cabecera', fn (Builder $q) => $q->where('nivel_id', $nivelId))
            ->orderBy('orden')->get();

        if ($rows->isEmpty()) {
            $this->dispatch('notify', ['type' => 'warning', 'message' => 'No hay registros confirmados para exportar.']);
            return null;
        }

        $nivelNombre = $rows->first()?->cabecera?->nivel?->nombre ?? 'nivel';
        $ciclo = CicloEscolar::query()->find($this->cicloEscolarId);
        $data = $rows->map(fn (PersonaNivelDetalle $r, int $index) => [
            'no' => $index + 1,
            'persona' => trim(collect([
                $r->cabecera?->persona?->titulo,
                $r->cabecera?->persona?->nombre,
                $r->cabecera?->persona?->apellido_paterno,
                $r->cabecera?->persona?->apellido_materno,
            ])->filter()->implode(' ')),
            'nivel' => $nivelNombre,
            'grado' => $r->grado?->nombre ?? 'General',
            'grupo' => $r->grupo?->asignacionGrupo?->nombre ?? 'General',
            'materia' => $r->personaRole?->rolePersona?->nombre ?? 'Sin función',
            'ingreso_seg' => optional($r->cabecera?->ingreso_seg)->format('d/m/Y') ?: '',
            'ingreso_sep' => optional($r->cabecera?->ingreso_sep)->format('d/m/Y') ?: '',
            'ingreso_ct' => optional($r->cabecera?->ingreso_ct)->format('d/m/Y') ?: '',
        ]);

        return Excel::download(
            new PlantillaExport($data, $nivelNombre . ' · ' . ($ciclo?->nombre ?? 'ciclo')),
            'plantilla_' . str($nivelNombre)->slug('_') . '_' . str($ciclo?->nombre ?? 'ciclo')->replace('-', '_') . '.xlsx'
        );
    }

    private function queryDetalles(): Builder
    {
        return PersonaNivelDetalle::query()
            ->whereNull('archivado_at')
            ->whereHas('cicloAsignacion.plantilla', fn (Builder $q) => $q->where('ciclo_escolar_id', $this->cicloEscolarId))
            ->when(trim($this->search) !== '', function (Builder $query) {
                $s = trim($this->search);
                $query->where(function (Builder $sub) use ($s) {
                    $sub->whereHas('cabecera.persona', function (Builder $p) use ($s) {
                        $p->where('nombre', 'like', "%{$s}%")
                            ->orWhere('apellido_paterno', 'like', "%{$s}%")
                            ->orWhere('apellido_materno', 'like', "%{$s}%")
                            ->orWhere('especialidad', 'like', "%{$s}%");
                    })
                        ->orWhereHas('personaRole.rolePersona', fn (Builder $r) => $r->where('nombre', 'like', "%{$s}%"))
                        ->orWhereHas('grado', fn (Builder $g) => $g->where('nombre', 'like', "%{$s}%"))
                        ->orWhereHas('grupo.asignacionGrupo', fn (Builder $g) => $g->where('nombre', 'like', "%{$s}%"));
                });
            });
    }

    public function render(PlantillaPersonalCicloService $service)
    {
        $rows = $this->queryDetalles()
            ->with([
                'cabecera.persona', 'cabecera.nivel', 'cicloAsignacion.plantilla',
                'grado', 'grupo.asignacionGrupo', 'grupo.generacion', 'grupo.semestre',
                'personaRole.rolePersona',
            ])
            ->where('estado', PersonaNivelDetalle::ESTADO_ACTIVO)
            ->orderBy('orden')->orderBy('id')->get();

        $porNivel = $rows->groupBy(fn (PersonaNivelDetalle $r) => $r->cabecera?->nivel?->nombre ?? 'Sin nivel');
        $secundaria = Nivel::query()->where('slug', 'secundaria')->first();
        $rowsSec = $rows->where('cabecera.nivel_id', $secundaria?->id);

        $profesoresSec = $rowsSec
            ->groupBy('persona_nivel_ciclo_id')
            ->map(function (Collection $items) {
                $primero = $items->first();
                $cab = $primero?->cabecera;
                $membresia = $primero?->cicloAsignacion;
                $p = $cab?->persona;

                return [
                    'membresia_id' => (int) ($membresia?->id ?? 0),
                    'membresia_orden' => (int) ($membresia?->orden ?? 999999),
                    'cabecera_id' => (int) ($cab?->id ?? 0),
                    'persona_id' => (int) ($p?->id ?? 0),
                    'nombre' => trim(collect([$p?->titulo, $p?->nombre, $p?->apellido_paterno, $p?->apellido_materno])->filter()->implode(' ')) ?: 'Sin nombre',
                    'especialidad' => $p?->especialidad,
                    'ingreso_seg' => $cab?->ingreso_seg,
                    'ingreso_sep' => $cab?->ingreso_sep,
                    'ingreso_ct' => $cab?->ingreso_ct,
                    'pendientes' => $items->where('confirmado', false)->count(),
                    'detalles' => $items->sortBy('orden')->values(),
                ];
            })
            ->sortBy(fn (array $x) => [$x['membresia_orden'], $x['nombre']])->values();

        $niveles = Nivel::query()->orderBy('id')->get();
        $plantillas = $service->plantillasDelCiclo((int) $this->cicloEscolarId)->keyBy('nivel_id');
        $ciclo = CicloEscolar::query()->find($this->cicloEscolarId);

        return view('livewire.persona-nivel.mostrar-persona-nivel', compact(
            'porNivel', 'profesoresSec', 'secundaria', 'niveles', 'plantillas', 'ciclo'
        ));
    }
}
