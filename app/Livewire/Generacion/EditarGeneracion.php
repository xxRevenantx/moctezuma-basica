<?php

namespace App\Livewire\Generacion;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Nivel;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarGeneracion extends Component
{
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
        $this->resetValidation();

        $generacion = Generacion::query()
            ->findOrFail($id);

        $this->fill([
            'generacionId' => $generacion->id,
            'nivel_id' => $generacion->nivel_id,
            'anio_ingreso' => (int) $generacion->anio_ingreso,
            'anio_egreso' => (int) $generacion->anio_egreso,
            'nombre' => $generacion->etiqueta,
            'ciclo_escolar_inicio_id' => $generacion->ciclo_escolar_inicio_id,
            'ciclo_escolar_fin_id' => $generacion->ciclo_escolar_fin_id,
            'fecha_inicio' => optional(
                $generacion->fecha_inicio
            )->format('Y-m-d'),
            'fecha_termino' => optional(
                $generacion->fecha_termino
            )->format('Y-m-d'),
        ]);

        $this->dispatch('editar-cargado');
    }

    public function actualizarGeneracion(): void
    {
        if (!$this->generacionId) {
            $this->addError(
                'nombre',
                'No se pudo identificar la generación seleccionada.'
            );

            return;
        }

        $data = $this->validate([
            'nivel_id' => [
                'required',
                'exists:niveles,id',
            ],
            'anio_ingreso' => [
                'required',
                'integer',
                'digits:4',
            ],
            'anio_egreso' => [
                'required',
                'integer',
                'digits:4',
                'gt:anio_ingreso',
            ],
            'nombre' => [
                'required',
                'string',
                'max:50',
            ],
            'ciclo_escolar_inicio_id' => [
                'nullable',
                'exists:ciclo_escolares,id',
            ],
            'ciclo_escolar_fin_id' => [
                'nullable',
                'exists:ciclo_escolares,id',
            ],
            'fecha_inicio' => [
                'required',
                'date',
            ],
            'fecha_termino' => [
                'required',
                'date',
                'after:fecha_inicio',
            ],
        ], [
            'nivel_id.required' => 'Selecciona el nivel educativo.',
            'nivel_id.exists' => 'El nivel seleccionado no es válido.',

            'anio_ingreso.required' => 'Escribe el año de ingreso.',
            'anio_ingreso.integer' => 'El año de ingreso debe ser numérico.',
            'anio_ingreso.digits' => 'El año de ingreso debe contener 4 dígitos.',

            'anio_egreso.required' => 'Escribe el año de egreso.',
            'anio_egreso.integer' => 'El año de egreso debe ser numérico.',
            'anio_egreso.digits' => 'El año de egreso debe contener 4 dígitos.',
            'anio_egreso.gt' => 'El año de egreso debe ser posterior al año de ingreso.',

            'nombre.required' => 'Escribe el nombre de la generación.',
            'nombre.max' => 'El nombre no puede superar los 50 caracteres.',

            'ciclo_escolar_inicio_id.exists' => 'El ciclo inicial no es válido.',
            'ciclo_escolar_fin_id.exists' => 'El ciclo final no es válido.',

            'fecha_inicio.required' => 'Selecciona la fecha de inicio.',
            'fecha_inicio.date' => 'La fecha de inicio no es válida.',

            'fecha_termino.required' => 'Selecciona la fecha de término.',
            'fecha_termino.date' => 'La fecha de término no es válida.',
            'fecha_termino.after' => 'La fecha de término debe ser posterior a la fecha de inicio.',
        ]);

        $existe = Generacion::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('anio_ingreso', $this->anio_ingreso)
            ->where('anio_egreso', $this->anio_egreso)
            ->whereKeyNot($this->generacionId)
            ->exists();

        if ($existe) {
            $this->addError(
                'anio_ingreso',
                'Ya existe esa generación en el nivel seleccionado.'
            );

            return;
        }

        $generacion = Generacion::query()
            ->findOrFail($this->generacionId);

        $generacion->update($data);

        $this->dispatch('refreshGeneraciones');
        $this->dispatch('cerrar-modal-editar');

        $this->dispatch('swal', [
            'title' => 'Generación actualizada',
            'text' => 'Los cambios se guardaron correctamente.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->limpiarFormulario();
    }

    public function cerrarModal(): void
    {
        $this->limpiarFormulario();
    }

    private function limpiarFormulario(): void
    {
        $this->reset([
            'generacionId',
            'nivel_id',
            'anio_ingreso',
            'anio_egreso',
            'nombre',
            'ciclo_escolar_inicio_id',
            'ciclo_escolar_fin_id',
            'fecha_inicio',
            'fecha_termino',
        ]);

        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.generacion.editar-generacion', [
            'niveles' => Nivel::query()
                ->orderBy('id')
                ->get([
                    'id',
                    'nombre',
                ]),

            'ciclosEscolares' => CicloEscolar::query()
                ->orderByDesc('inicio_anio')
                ->get([
                    'id',
                    'inicio_anio',
                    'fin_anio',
                ]),
        ]);
    }
}
