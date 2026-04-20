<?php

namespace App\Livewire\Periodo;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\MesesBachillerato;
use App\Models\Nivel;
use App\Models\Periodos;
use App\Models\Semestre;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarPeriodo extends Component
{
    public $periodo_id = null;
    public $periodo_nombre = null;

    public $nivel_id = null;
    public $generacion_id = null;
    public $semestre_id = null;
    public $ciclo_escolar_id = null;
    public $mes_bachillerato_id = null;
    public $fecha_inicio = null;
    public $fecha_fin = null;

    public function getEsBachilleratoProperty(): bool
    {
        return (int) $this->nivel_id === 4;
    }

    /**
     * Cuando cambia el nivel:
     * - si no es bachillerato, limpio generación, semestre y mes
     * - si sí es bachillerato, limpio generación para volver a elegirla correctamente
     */
    public function updatedNivelId($value): void
    {
        if ((int) $value !== 4) {
            $this->generacion_id = null;
            $this->semestre_id = null;
            $this->mes_bachillerato_id = null;
        } else {
            $this->generacion_id = null;
        }

        $this->resetValidation([
            'generacion_id',
            'semestre_id',
            'mes_bachillerato_id',
        ]);
    }

    protected function rules(): array
    {
        $rules = [
            'nivel_id' => 'required|exists:niveles,id',
            'ciclo_escolar_id' => 'required|exists:ciclo_escolares,id',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after:fecha_inicio',
        ];

        if ($this->esBachillerato) {
            $rules['generacion_id'] = 'required|exists:generaciones,id';
            $rules['semestre_id'] = 'required|exists:semestres,id';
            $rules['mes_bachillerato_id'] = 'required|exists:meses_bachilleratos,id';
        } else {
            $rules['generacion_id'] = 'nullable';
            $rules['semestre_id'] = 'nullable';
            $rules['mes_bachillerato_id'] = 'nullable';
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'nivel_id.required' => 'El nivel es obligatorio.',
            'nivel_id.exists' => 'El nivel seleccionado no es válido.',

            'ciclo_escolar_id.required' => 'El ciclo escolar es obligatorio.',
            'ciclo_escolar_id.exists' => 'El ciclo escolar seleccionado no es válido.',

            'generacion_id.required' => 'La generación es obligatoria para bachillerato.',
            'generacion_id.exists' => 'La generación seleccionada no es válida.',

            'semestre_id.required' => 'El semestre es obligatorio para bachillerato.',
            'semestre_id.exists' => 'El semestre seleccionado no es válido.',

            'mes_bachillerato_id.required' => 'El mes del periodo es obligatorio para bachillerato.',
            'mes_bachillerato_id.exists' => 'El mes seleccionado no es válido.',

            'fecha_inicio.date' => 'La fecha de inicio no es válida.',
            'fecha_fin.date' => 'La fecha de fin no es válida.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ];
    }

    #[On('editarModal')]
    public function editarModal($id): void
    {
        $periodo = Periodos::findOrFail($id);

        $this->periodo_id = $periodo->id;
        $this->nivel_id = $periodo->nivel_id;
        $this->generacion_id = $periodo->generacion_id;
        $this->semestre_id = $periodo->semestre_id;
        $this->ciclo_escolar_id = $periodo->ciclo_escolar_id;
        $this->mes_bachillerato_id = $periodo->mes_bachillerato_id;
        $this->fecha_inicio = $periodo->fecha_inicio;
        $this->fecha_fin = $periodo->fecha_fin;

        $this->periodo_nombre = $periodo->mesesBachillerato->meses
            ?? ($periodo->cicloEscolar->inicio_anio ?? 'Periodo');

        $this->resetValidation();

        $this->dispatch('editar-cargado');
    }

    public function actualizarPeriodoBachillerato(): void
    {
        $this->validate();

        $periodo = Periodos::findOrFail($this->periodo_id);

        /**
         * Valido que la generación pertenezca al nivel seleccionado,
         * pero solo si es bachillerato.
         */
        if ($this->esBachillerato) {
            $generacionValida = Generacion::query()
                ->where('id', $this->generacion_id)
                ->where('nivel_id', $this->nivel_id)
                ->exists();

            if (! $generacionValida) {
                $this->addError('generacion_id', 'La generación no pertenece al nivel seleccionado.');
                return;
            }
        }

        if ($this->esBachillerato) {
            $existe = Periodos::query()
                ->where('id', '!=', $this->periodo_id)
                ->where('nivel_id', $this->nivel_id)
                ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                ->where('generacion_id', $this->generacion_id)
                ->where('semestre_id', $this->semestre_id)
                ->where('mes_bachillerato_id', $this->mes_bachillerato_id)
                ->exists();

            if ($existe) {
                $this->addError('ciclo_escolar_id', 'El periodo para esta asignación ya existe.');
                return;
            }
        }

        $periodo->update([
            'nivel_id' => $this->nivel_id,
            'generacion_id' => $this->esBachillerato ? $this->generacion_id : null,
            'semestre_id' => $this->esBachillerato ? $this->semestre_id : null,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'mes_bachillerato_id' => $this->esBachillerato ? $this->mes_bachillerato_id : null,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
        ]);

        $this->dispatch('swal', [
            'title' => '¡Periodo actualizado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('cerrar-modal-editar');
        $this->dispatch('refreshPeriodos');

        $this->cerrarModal();
    }

    public function cerrarModal(): void
    {
        $this->reset([
            'periodo_id',
            'periodo_nombre',
            'nivel_id',
            'generacion_id',
            'semestre_id',
            'ciclo_escolar_id',
            'mes_bachillerato_id',
            'fecha_inicio',
            'fecha_fin',
        ]);

        $this->resetValidation();
    }

    public function render()
    {
        $generaciones = Generacion::query()
            ->when((int) $this->nivel_id === 4, function ($query) {
                $query->where('nivel_id', $this->nivel_id);
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->orderBy('anio_ingreso', 'desc')
            ->get();

        return view('livewire.periodo.editar-periodo', [
            'niveles' => Nivel::query()->orderBy('nombre')->get(),
            'generaciones' => $generaciones,
            'semestres' => Semestre::query()->orderBy('numero')->get(),
            'ciclosEscolares' => CicloEscolar::query()->orderBy('inicio_anio', 'desc')->get(),
            'meses' => MesesBachillerato::query()->orderBy('id')->get(),
        ]);
    }
}
