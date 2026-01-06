<?php

namespace App\Livewire\PersonaNivel;

use App\Models\Nivel;
use App\Models\PersonaNivel;
use App\Models\PersonaNivelDetalle;
use App\Models\CicloEscolar;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class MostrarPersonaNivel extends Component
{
    public string $search = '';

    #[On('refreshPersonaNivelList')]
    public function refreshPersonaNivelList(): void
    {
        $this->dispatch('$refresh');
    }

    /**
     * ✅ NORMALIZAR ORDEN GLOBAL EN persona_nivel
     * Deja el orden como 1..N SIN repetirse (consecutivo global)
     */
    private function normalizarOrdenPersonaNivelGlobal(): void
    {
        DB::transaction(function () {
            $ids = PersonaNivel::query()
                ->orderBy('nivel_id')
                ->orderByRaw('CASE WHEN orden IS NULL THEN 1 ELSE 0 END')
                ->orderBy('orden')
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $i = 1;
            foreach ($ids as $id) {
                PersonaNivel::whereKey($id)->update(['orden' => $i++]);
            }
        });
    }

    /**
     * ✅ Recalcula el orden de CABECERAS (persona_nivel) basándose en el orden actual de DETALLES
     * Esto permite que al arrastrar filas de detalles, también cambie persona_nivel.orden
     */
    private function recalcularOrdenCabecerasPorDetalles(int $nivelId): void
    {
        if (! $nivelId) return;

        // Ordena cabeceras según el primer detalle que aparezca en el nivel
        $cabeceraIdsOrdenadas = PersonaNivelDetalle::query()
            ->whereHas('cabecera', fn ($q) => $q->where('nivel_id', $nivelId))
            ->orderBy('orden')
            ->orderBy('id')
            ->pluck('persona_nivel_id')
            ->unique()
            ->values();

        $i = 1;
        foreach ($cabeceraIdsOrdenadas as $cabId) {
            PersonaNivel::whereKey((int) $cabId)->update(['orden' => $i++]);
        }
    }

    // ✅ ELIMINAR: DETALLE
    public function eliminar(int $id): void
    {
        $detalle = PersonaNivelDetalle::with('cabecera')->find($id);

        if (! $detalle) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Registro no encontrado.']);
            return;
        }

        $nivelId    = (int) ($detalle->cabecera?->nivel_id ?? 0);
        $cabeceraId = (int) ($detalle->persona_nivel_id ?? 0);

        $detalle->delete();

        // ✅ si cabecera se queda sin detalles, borrar cabecera
        if ($cabeceraId) {
            $tieneMas = PersonaNivelDetalle::where('persona_nivel_id', $cabeceraId)->exists();
            if (! $tieneMas) {
                PersonaNivel::whereKey($cabeceraId)->delete();
            }
        }

        // ✅ Reordenar detalles restantes del mismo NIVEL + recalcular cabeceras
        if ($nivelId) {
            DB::transaction(function () use ($nivelId) {

                // 1) normalizar detalles (1..N) del nivel
                $all = PersonaNivelDetalle::query()
                    ->whereHas('cabecera', fn ($q) => $q->where('nivel_id', $nivelId))
                    ->orderBy('orden')
                    ->orderBy('id')
                    ->pluck('id')
                    ->all();

                $i = 1;
                foreach ($all as $pid) {
                    PersonaNivelDetalle::whereKey($pid)->update(['orden' => $i++]);
                }

                // 2) recalcular persona_nivel.orden basado en los detalles
                $this->recalcularOrdenCabecerasPorDetalles($nivelId);
            });
        }

        // 3) normalizar global persona_nivel (1..N sin repetirse)
        $this->normalizarOrdenPersonaNivelGlobal();

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Asignación eliminada correctamente.']);
        $this->dispatch('$refresh');
    }

    /**
     * ✅ Orden lineal por NIVEL sobre DETALLES (otros niveles)
     * AHORA TAMBIÉN actualiza persona_nivel.orden según el nuevo orden de detalles
     */
    public function ordenarJs(int $nivelId, array $ids): void
    {
        $ids = collect($ids)->map(fn ($v) => (int) $v)->filter()->values();
        if ($ids->isEmpty() || ! $nivelId) return;

        $validIds = PersonaNivelDetalle::query()
            ->whereHas('cabecera', fn ($q) => $q->where('nivel_id', $nivelId))
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();

        $validSet = array_flip($validIds);

        DB::transaction(function () use ($ids, $validSet, $nivelId) {
            // 1) aplicar orden recibido
            $i = 1;
            foreach ($ids as $id) {
                if (! isset($validSet[$id])) continue;
                PersonaNivelDetalle::whereKey($id)->update(['orden' => $i++]);
            }

            // 2) normalizar consecutivo por nivel
            $all = PersonaNivelDetalle::query()
                ->whereHas('cabecera', fn ($q) => $q->where('nivel_id', $nivelId))
                ->orderBy('orden')
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $j = 1;
            foreach ($all as $pid) {
                PersonaNivelDetalle::whereKey($pid)->update(['orden' => $j++]);
            }

            // ✅ 3) recalcular orden de CABECERAS (persona_nivel) según el orden actual de detalles
            $this->recalcularOrdenCabecerasPorDetalles($nivelId);
        });

        // ✅ 4) normalizar global persona_nivel (1..N sin repetirse)
        $this->normalizarOrdenPersonaNivelGlobal();

        $this->dispatch('$refresh');
    }

    /**
     * ✅ Orden SOLO dentro de un profesor (cabecera) en SECUNDARIA (detalles)
     * (Aquí NO se toca persona_nivel.orden porque solo cambias filas internas)
     */
    public function ordenarSecJs(int $nivelId, int $cabeceraId, array $ids): void
    {
        $ids = collect($ids)->map(fn ($v) => (int) $v)->filter()->values();
        if ($ids->isEmpty() || ! $nivelId || ! $cabeceraId) return;

        $validIds = PersonaNivelDetalle::query()
            ->where('persona_nivel_id', $cabeceraId)
            ->whereHas('cabecera', fn ($q) => $q->where('nivel_id', $nivelId))
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();

        $validSet = array_flip($validIds);

        DB::transaction(function () use ($cabeceraId, $ids, $validSet) {
            $i = 1;
            foreach ($ids as $id) {
                if (! isset($validSet[$id])) continue;
                PersonaNivelDetalle::whereKey($id)->update(['orden' => $i++]);
            }

            // normalizar consecutivo por cabecera
            $all = PersonaNivelDetalle::query()
                ->where('persona_nivel_id', $cabeceraId)
                ->orderBy('orden')
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $j = 1;
            foreach ($all as $pid) {
                PersonaNivelDetalle::whereKey($pid)->update(['orden' => $j++]);
            }
        });

        $this->dispatch('$refresh');
    }

    /**
     * ✅ Ordenar PERSONAS (cards) por NIVEL (persona_nivel)
     * Firma: ordenarPersonasJs(nivelId, cabeceraIds)
     */
    public function ordenarPersonasJs(int $nivelId, array $cabeceraIds): void
    {
        $cabeceraIds = collect($cabeceraIds)->map(fn ($v) => (int) $v)->filter()->values();
        if ($cabeceraIds->isEmpty() || ! $nivelId) return;

        $validIds = PersonaNivel::query()
            ->where('nivel_id', $nivelId)
            ->whereIn('id', $cabeceraIds)
            ->pluck('id')
            ->all();

        $validSet = array_flip($validIds);

        DB::transaction(function () use ($nivelId, $cabeceraIds, $validSet) {
            // 1) aplica el orden drag dentro del nivel
            $i = 1;
            foreach ($cabeceraIds as $id) {
                if (! isset($validSet[$id])) continue;
                PersonaNivel::whereKey($id)->update(['orden' => $i++]);
            }

            // 2) normaliza consecutivo dentro del nivel
            $allNivel = PersonaNivel::query()
                ->where('nivel_id', $nivelId)
                ->orderByRaw('CASE WHEN orden IS NULL THEN 1 ELSE 0 END')
                ->orderBy('orden')
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $j = 1;
            foreach ($allNivel as $pid) {
                PersonaNivel::whereKey($pid)->update(['orden' => $j++]);
            }
        });

        // 3) normaliza GLOBAL (1..N sin repetirse)
        $this->normalizarOrdenPersonaNivelGlobal();

        $this->dispatch('$refresh');
    }

    public function render()
    {
        $rows = PersonaNivelDetalle::query()
            ->with([
                'cabecera.persona',
                'cabecera.nivel',
                'cabecera:id,persona_id,nivel_id,orden,ingreso_seg,ingreso_sep,ingreso_ct',
                'grado',
                'grupo',
                'personaRole.rolePersona',
            ])
            ->when($this->search, function ($q) {
                $s = trim($this->search);

                $q->where(function ($qq) use ($s) {
                    $qq->whereHas('cabecera.persona', function ($p) use ($s) {
                        $p->where('nombre', 'like', "%{$s}%")
                            ->orWhere('apellido_paterno', 'like', "%{$s}%")
                            ->orWhere('apellido_materno', 'like', "%{$s}%")
                            ->orWhere('especialidad', 'like', "%{$s}%");
                    })
                    ->orWhereHas('cabecera.nivel', fn ($n) => $n->where('nombre', 'like', "%{$s}%"))
                    ->orWhereHas('grado', fn ($g) => $g->where('nombre', 'like', "%{$s}%"))
                    ->orWhereHas('grupo', fn ($gr) => $gr->where('nombre', 'like', "%{$s}%"))
                    ->orWhereHas('personaRole.rolePersona', fn ($r) => $r->where('nombre', 'like', "%{$s}%"));
                });
            })
            ->orderBy(
                PersonaNivel::select('nivel_id')
                    ->whereColumn('persona_nivel.id', 'persona_nivel_detalles.persona_nivel_id')
                    ->limit(1)
            )
            ->orderBy('orden')
            ->get();

        $porNivel = $rows->groupBy(fn ($r) => $r->cabecera?->nivel?->nombre ?? 'Sin nivel');

        $secundaria = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->where('slug', 'secundaria')
            ->first();

        $rowsSec = $rows->filter(function ($r) use ($secundaria) {
            $nivelId = (int) ($r->cabecera?->nivel_id ?? 0);

            if ($secundaria) return $nivelId === (int) $secundaria->id;

            $nombre = mb_strtolower((string) ($r->cabecera?->nivel?->nombre ?? ''));
            return str_contains($nombre, 'secund');
        });

        $profesoresSec = $rowsSec
            ->groupBy(fn ($r) => (int) ($r->cabecera?->persona_id ?? 0))
            ->map(function ($items) {
                $cab = $items->first()?->cabecera;
                $p   = $cab?->persona;

                $nombreCompleto = trim(
                    ($p->nombre ?? '') . ' ' . ($p->apellido_paterno ?? '') . ' ' . ($p->apellido_materno ?? '')
                );

                $materias = $items
                    ->map(fn ($r) => $r->personaRole?->rolePersona?->nombre)
                    ->filter()
                    ->unique()
                    ->values();

                $detalles = $items->map(function ($r) use ($cab) {
                    return [
                        'id' => $r->id,
                        'materia' => $r->personaRole?->rolePersona?->nombre,
                        'grado' => $r->grado?->nombre,
                        'grupo' => $r->grupo?->nombre,
                        'ingreso_seg' => $cab?->ingreso_seg,
                        'ingreso_sep' => $cab?->ingreso_sep,
                        'ingreso_ct'  => $cab?->ingreso_ct,
                    ];
                })->values();

                return [
                    'cabecera_id'    => (int) ($cab?->id ?? 0),
                    'cabecera_orden' => (int) ($cab?->orden ?? 999999),
                    'persona_id'     => (int) ($p->id ?? 0),
                    'nombre'         => $nombreCompleto ?: 'Sin nombre',
                    'especialidad'   => $p->especialidad ?? null,
                    'ingreso_seg'    => $cab?->ingreso_seg,
                    'ingreso_sep'    => $cab?->ingreso_sep,
                    'ingreso_ct'     => $cab?->ingreso_ct,
                    'total_asignaciones' => $items->count(),
                    'total_materias'     => $materias->count(),
                    'materias'       => $materias,
                    'detalles'       => $detalles,
                ];
            })
            ->sortBy(fn ($x) => [$x['cabecera_orden'], $x['nombre']])
            ->values();

        $niveles = Nivel::orderBy('id')->get();
        $ciclos  = CicloEscolar::orderBy('id', 'desc')->get();

        return view('livewire.persona-nivel.mostrar-persona-nivel', compact(
            'porNivel',
            'profesoresSec',
            'secundaria',
            'niveles',
            'ciclos'
        ));
    }
}
