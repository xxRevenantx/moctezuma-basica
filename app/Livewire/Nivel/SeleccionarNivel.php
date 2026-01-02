<?php

namespace App\Livewire\Nivel;

use App\Models\Grado;
use App\Models\Nivel;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SeleccionarNivel extends Component
{
    public string $slug = '';
    public string $search = '';



    public ?int $seleccionandoId = null; // ✅ NUEVO

    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }

    #[Computed]
    public function nivel(): ?Nivel
    {
        return Nivel::query()
            ->where('slug', $this->slug)
            ->first();
    }

    #[Computed]
    public function grados()
    {
        $nivel = $this->nivel;
        if (! $nivel) return collect();

        return Grado::query()
            ->where('nivel_id', $nivel->id)
            ->when($this->search, fn ($q) => $q->where('nombre', 'like', '%'.trim($this->search).'%'))
            ->withCount([
                // ✅ TOTAL (inscripciones en ese grado y nivel)
                'inscripciones as total' => fn ($q) =>
                    $q->where('nivel_id', $nivel->id)
                      ->where('activo', 1),

                // ✅ HOMBRES
                'inscripciones as hombres' => fn ($q) =>
                    $q->where('nivel_id', $nivel->id)
                      ->where('activo', 1)
                      ->where('genero', 'H'),

                // ✅ MUJERES
                'inscripciones as mujeres' => fn ($q) =>
                    $q->where('nivel_id', $nivel->id)
                      ->where('activo', 1)
                      ->where('genero', 'M'),
            ])
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get()
            ->map(function ($g) {
                $total = (int) ($g->total ?? 0);
                $h = (int) ($g->hombres ?? 0);
                $m = (int) ($g->mujeres ?? 0);

                $g->pct_h = $total > 0 ? round(($h / $total) * 100) : 0;
                $g->pct_m = $total > 0 ? round(($m / $total) * 100) : 0;

                return $g;
            });
    }

 public function seleccionar(int $gradoId)
{
      $this->seleccionandoId = $gradoId; // ✅ solo marca el presionado

    $nivel = $this->nivel;
    abort_unless($nivel, 404);

    // Seguridad: el grado debe pertenecer al nivel de la URL


    return redirect()->route('submodulos.nivel', [
        'slug_nivel' => $nivel->slug,
        'accion'     => 'matricula',       // si quieres default
    ]);
}



    public function render()
    {
        return view('livewire.nivel.seleccionar-nivel');
    }
}
