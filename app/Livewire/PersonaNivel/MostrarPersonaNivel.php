<?php

namespace App\Livewire\PersonaNivel;

use App\Models\PersonaNivel;
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

    // ELIMINAR
    public function eliminar(int $id): void
    {
        $pn = PersonaNivel::find($id);
        if (! $pn) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Registro no encontrado.']);
            return;
        }

        $pn->delete();

        // Reordenar los restantes del mismo nivel
        DB::transaction(function () use ($pn) {
            $all = PersonaNivel::query()
                ->where('nivel_id', $pn->nivel_id)
                ->orderBy('orden')
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $i = 1;
            foreach ($all as $pid) {
                PersonaNivel::whereKey($pid)->update(['orden' => $i++]);
            }
        });

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Registro eliminado correctamente.']);
        $this->dispatch('$refresh');
    }

    /**
     * ✅ Orden lineal por NIVEL (1..N)
     * Firma compatible con el JS del blade: ordenarJs(nivelId, ids)
     */
    public function ordenarJs(int $nivelId, array $ids): void
    {
        $ids = collect($ids)->map(fn ($v) => (int) $v)->filter()->values();
        if ($ids->isEmpty()) return;

        // Seguridad: solo IDs del mismo nivel
        $validIds = PersonaNivel::query()
            ->where('nivel_id', $nivelId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();

        $validSet = array_flip($validIds);

        DB::transaction(function () use ($ids, $validSet, $nivelId) {
            // aplica el nuevo orden
            $i = 1;
            foreach ($ids as $id) {
                if (!isset($validSet[$id])) continue;
                PersonaNivel::whereKey($id)->update(['orden' => $i++]);
            }

            // normaliza 1..N por si quedó hueco
            $all = PersonaNivel::query()
                ->where('nivel_id', $nivelId)
                ->orderBy('orden')
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $j = 1;
            foreach ($all as $pid) {
                PersonaNivel::whereKey($pid)->update(['orden' => $j++]);
            }
        });

        $this->dispatch('$refresh');
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
            // ✅ orden lineal por NIVEL
            ->orderBy('nivel_id')
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        // ✅ Solo collapse por NIVEL
        $porNivel = $rows->groupBy(fn ($r) => $r->nivel?->nombre ?? 'Sin nivel');

        return view('livewire.persona-nivel.mostrar-persona-nivel', compact('porNivel'));
    }
}
