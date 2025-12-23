<?php

namespace App\Livewire\PersonaNivel;

use App\Models\PersonaNivel;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class MostrarPersonaNivel extends Component
{
    public string $search = '';

    public function updatingSearch(): void
    {
        // si luego agregas paginación, aquí resetPage()
    }

    #[On('refreshPersonaNivelList')]
    public function refreshPersonaNivelList(): void
    {
        $this->dispatch('$refresh');
    }

    /**
     * Scope inclusivo: SOLO afecta al mismo (nivel_id + grupo_id)
     * (si grupo_id es null, aplica whereNull)
     */
    private function scopedQuery(PersonaNivel $row)
    {
        return PersonaNivel::query()
            ->where('nivel_id', $row->nivel_id)
            ->when(
                is_null($row->grupo_id),
                fn ($q) => $q->whereNull('grupo_id'),
                fn ($q) => $q->where('grupo_id', $row->grupo_id)
            );
    }

    public function subir(int $personaNivelId): void
    {
        $actual = PersonaNivel::findOrFail($personaNivelId);

        // Si por alguna razón no tiene orden, normaliza primero
        if (empty($actual->orden)) {
            $this->normalizarOrden($actual->nivel_id, $actual->grupo_id);
            $actual->refresh();
        }

        $previo = $this->scopedQuery($actual)
            ->where('orden', '<', $actual->orden)
            ->orderBy('orden', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if (! $previo) return;

        DB::transaction(function () use ($actual, $previo) {
            [$actual->orden, $previo->orden] = [$previo->orden, $actual->orden];
            $actual->save();
            $previo->save();
        });

        // ✅ deja secuencia limpia 1..N en ese nivel+grupo
        $this->normalizarOrden($actual->nivel_id, $actual->grupo_id);

        $this->dispatch('$refresh');
    }

    public function bajar(int $personaNivelId): void
    {
        $actual = PersonaNivel::findOrFail($personaNivelId);

        if (empty($actual->orden)) {
            $this->normalizarOrden($actual->nivel_id, $actual->grupo_id);
            $actual->refresh();
        }

        $siguiente = $this->scopedQuery($actual)
            ->where('orden', '>', $actual->orden)
            ->orderBy('orden', 'asc')
            ->orderBy('id', 'asc')
            ->first();

        if (! $siguiente) return;

        DB::transaction(function () use ($actual, $siguiente) {
            [$actual->orden, $siguiente->orden] = [$siguiente->orden, $actual->orden];
            $actual->save();
            $siguiente->save();
        });

        $this->normalizarOrden($actual->nivel_id, $actual->grupo_id);

        $this->dispatch('$refresh');
    }

    /**
     * Normaliza el orden (1..N) dentro del mismo (nivel_id + grupo_id)
     * ✅ inclusivo: NO toca otros niveles ni otros grupos
     */
    public function normalizarOrden(int $nivelId, ?int $grupoId): void
    {
        $items = PersonaNivel::query()
            ->where('nivel_id', $nivelId)
            ->when(
                is_null($grupoId),
                fn ($q) => $q->whereNull('grupo_id'),
                fn ($q) => $q->where('grupo_id', $grupoId)
            )
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        DB::transaction(function () use ($items) {
            $i = 1;
            foreach ($items as $row) {
                if ((int) $row->orden !== $i) {
                    $row->orden = $i;
                    $row->save();
                }
                $i++;
            }
        });
    }

    public function render()
    {
        $rows = PersonaNivel::with([
                'persona.personaRoles.rolePersona',
                'nivel',
                'grado',
                'grupo',
            ])
            ->when($this->search, function ($q) {
                $s = trim($this->search);

                $q->where(function ($qq) use ($s) {
                    $qq->whereHas('persona', function ($p) use ($s) {
                            $p->where('nombre', 'like', "%{$s}%")
                              ->orWhere('apellido_paterno', 'like', "%{$s}%")
                              ->orWhere('apellido_materno', 'like', "%{$s}%")
                              ->orWhere('especialidad', 'like', "%{$s}%");
                        })
                        ->orWhereHas('nivel', fn ($n) => $n->where('nombre', 'like', "%{$s}%"))
                        ->orWhereHas('grado', fn ($g) => $g->where('nombre', 'like', "%{$s}%"))
                        ->orWhereHas('grupo', fn ($gr) => $gr->where('nombre', 'like', "%{$s}%"))
                        ->orWhereHas('persona.personaRoles.rolePersona', fn ($r) => $r->where('nombre', 'like', "%{$s}%"));
                });
            })
            ->orderBy('nivel_id')
            ->orderBy('grupo_id')
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        // NIVEL -> GRUPO
        $personalNivel = $rows
            ->groupBy(fn ($r) => $r->nivel?->nombre ?? 'Sin nivel')
            ->map(fn ($itemsNivel) => $itemsNivel->groupBy(fn ($r) => $r->grupo?->nombre ?? 'Sin grupo'));

        return view('livewire.persona-nivel.mostrar-persona-nivel', compact('personalNivel'));
    }
}
