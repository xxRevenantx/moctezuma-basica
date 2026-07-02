<?php

namespace App\Livewire\Generacion;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Nivel;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CrearGeneracion extends Component
{
    public ?int $nivel_id = null;
    public ?int $anio_ingreso = null;
    public ?int $anio_egreso = null;
    public string $nombre = '';
    public ?int $ciclo_escolar_inicio_id = null;
    public ?int $ciclo_escolar_fin_id = null;
    public ?string $fecha_inicio = null;
    public ?string $fecha_termino = null;

    public function updatedAnioIngreso(): void { $this->actualizarNombre(); }
    public function updatedAnioEgreso(): void { $this->actualizarNombre(); }

    private function actualizarNombre(): void
    {
        if ($this->anio_ingreso && $this->anio_egreso) {
            $this->nombre = $this->anio_ingreso . '-' . $this->anio_egreso;
        }
    }

    public function guardarGeneracion(): void
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
            ->exists();

        if ($existe) {
            $this->addError('anio_ingreso', 'La generación ya existe en este nivel.');
            return;
        }

        Generacion::query()->create($data + ['status' => true]);
        $this->reset();
        $this->dispatch('refreshGeneraciones');
        $this->dispatch('swal', ['title' => 'Generación creada', 'icon' => 'success', 'position' => 'top-end']);
    }

    public function render()
    {
        return view('livewire.generacion.crear-generacion', [
            'niveles' => Nivel::query()->orderBy('id')->get(),
            'ciclosEscolares' => CicloEscolar::query()->orderByDesc('inicio_anio')->get(),
        ]);
    }
}
