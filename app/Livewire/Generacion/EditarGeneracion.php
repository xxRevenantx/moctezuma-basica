<?php

namespace App\Livewire\Generacion;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Nivel;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarGeneracion extends Component
{
    public bool $open = false;
    public ?int $generacionId = null;
    public ?int $nivel_id = null;
    public ?int $anio_ingreso = null;
    public ?int $anio_egreso = null;
    public string $nombre = '';
    public ?int $ciclo_escolar_inicio_id = null;
    public ?int $ciclo_escolar_fin_id = null;
    public ?string $fecha_inicio = null;
    public ?string $fecha_termino = null;

    #[On('editarModal')]
    public function editarModal(int $id): void
    {
        $g = Generacion::query()->findOrFail($id);
        $this->fill([
            'generacionId' => $g->id,
            'nivel_id' => $g->nivel_id,
            'anio_ingreso' => (int) $g->anio_ingreso,
            'anio_egreso' => (int) $g->anio_egreso,
            'nombre' => $g->etiqueta,
            'ciclo_escolar_inicio_id' => $g->ciclo_escolar_inicio_id,
            'ciclo_escolar_fin_id' => $g->ciclo_escolar_fin_id,
            'fecha_inicio' => optional($g->fecha_inicio)->format('Y-m-d'),
            'fecha_termino' => optional($g->fecha_termino)->format('Y-m-d'),
            'open' => true,
        ]);
        $this->dispatch('editar-cargado');
    }

    public function actualizarGeneracion(): void
    {
        $data = $this->validate([
            'nivel_id' => ['required', 'exists:niveles,id'],
            'anio_ingreso' => ['required', 'integer', 'digits:4'],
            'anio_egreso' => ['required', 'integer', 'digits:4', 'gt:anio_ingreso'],
            'nombre' => ['required', 'string', 'max:50'],
            'ciclo_escolar_inicio_id' => ['nullable', 'exists:ciclo_escolares,id'],
            'ciclo_escolar_fin_id' => ['nullable', 'exists:ciclo_escolares,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_termino' => ['required', 'date', 'after:fecha_inicio'],
        ]);

        $existe = Generacion::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('anio_ingreso', $this->anio_ingreso)
            ->where('anio_egreso', $this->anio_egreso)
            ->where('id', '!=', $this->generacionId)
            ->exists();
        if ($existe) {
            $this->addError('anio_ingreso', 'Ya existe esa generación en el nivel seleccionado.');
            return;
        }

        Generacion::query()->findOrFail($this->generacionId)->update($data);
        $this->dispatch('refreshGeneraciones');
        $this->dispatch('cerrar-modal-editar');
        $this->dispatch('swal', ['title' => 'Generación actualizada', 'icon' => 'success', 'position' => 'top-end']);
        $this->cerrarModal();
    }

    public function cerrarModal(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.generacion.editar-generacion', [
            'niveles' => Nivel::query()->orderBy('id')->get(),
            'ciclosEscolares' => CicloEscolar::query()->orderByDesc('inicio_anio')->get(),
        ]);
    }
}
