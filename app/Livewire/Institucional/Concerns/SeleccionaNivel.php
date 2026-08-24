<?php

namespace App\Livewire\Institucional\Concerns;

use App\Models\Nivel;
use Illuminate\Support\Collection;

trait SeleccionaNivel
{
    public Collection $niveles;
    public string $slug_nivel = '';

    protected function inicializarSelectorNivel(?string $slugPreferido = null): void
    {
        abort_unless((bool) (auth()->user()?->is_admin ?? false), 403);

        $this->niveles = Nivel::query()
            ->orderBy('id')
            ->get(['id', 'nombre', 'slug']);

        $solicitado = trim((string) ($slugPreferido ?: request('nivel', '')));

        if ($solicitado === '' || ! $this->niveles->contains('slug', $solicitado)) {
            $solicitado = (string) ($this->niveles->first()?->slug ?? '');
        }

        $this->slug_nivel = $solicitado;
    }

    public function seleccionarNivel(string $slug): void
    {
        $slug = trim($slug);

        abort_unless($this->niveles->contains('slug', $slug), 404);

        if ($this->slug_nivel === $slug) {
            return;
        }

        $this->slug_nivel = $slug;
        $this->alCambiarNivel();
    }

    protected function alCambiarNivel(): void
    {
        // Los módulos pueden sobrescribir este hook para limpiar filtros locales.
    }

    public function getNivelActualProperty(): ?Nivel
    {
        return $this->niveles->firstWhere('slug', $this->slug_nivel);
    }
}
