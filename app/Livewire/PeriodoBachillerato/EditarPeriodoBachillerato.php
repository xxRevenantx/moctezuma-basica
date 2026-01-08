<?php

namespace App\Livewire\PeriodoBachillerato;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\MesesBachillerato;
use App\Models\PeriodosBachillerato;
use App\Models\Semestre;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarPeriodoBachillerato extends Component
{
    public bool $open = false;

    // ID del periodo a editar
    public ?int $periodoId = null;

    // Campos (mismos que crear)
    public $generacion_id = null;
    public $semestre_id = null;
    public $ciclo_escolar_id = null;

    public $mes_id = null;
    public $fecha_inicio = null;
    public $fecha_fin = null;

    // UI
    public ?string $periodo_nombre = null;

    #[On('editarModal')]
    public function editarModal(int $id): void
    {
        $periodo = PeriodosBachillerato::findOrFail($id);

        $this->periodoId = $periodo->id;
        $this->generacion_id = $periodo->generacion_id;
        $this->semestre_id = $periodo->semestre_id;
        $this->ciclo_escolar_id = $periodo->ciclo_escolar_id;
        $this->mes_id = $periodo->mes_id;
        $this->fecha_inicio = $periodo->fecha_inicio;
        $this->fecha_fin = $periodo->fecha_fin;

        // Texto para el badge (ajústalo a tu gusto)
        $this->periodo_nombre = trim(
            ($periodo->cicloEscolar?->inicio_anio ?? '') . '-' . ($periodo->cicloEscolar?->fin_anio ?? '')
        );

        // abre modal (Alpine)
        $this->dispatch('abrir-modal-editar');

        // quita loading del modal
        $this->dispatch('editar-cargado');
    }

    public function actualizarPeriodoBachillerato(): void
    {
        $this->validate([
            "generacion_id" => "required|exists:generaciones,id",
            "semestre_id" => "required|exists:semestres,id",
            "ciclo_escolar_id" => "required|exists:ciclo_escolares,id",
            "mes_id" => "required|exists:meses_bachilleratos,id",
            "fecha_inicio" => "nullable|date",
            "fecha_fin" => "nullable|date|after:fecha_inicio",
        ], [
            'generacion_id.required' => 'La generación es obligatoria.',
            'generacion_id.exists' => 'La generación seleccionada no es válida.',
            'semestre_id.required' => 'El semestre es obligatorio.',
            'semestre_id.exists' => 'El semestre seleccionado no es válido.',
            'ciclo_escolar_id.required' => 'El ciclo escolar es obligatorio.',
            'ciclo_escolar_id.exists' => 'El ciclo escolar seleccionado no es válido.',
            'mes_id.required' => 'El mes es obligatorio.',
            'mes_id.exists' => 'El mes seleccionado no es válido.',
            'fecha_inicio.date' => 'La fecha de inicio no es una fecha válida.',
            'fecha_fin.date' => 'La fecha de fin no es una fecha válida.',
            'fecha_fin.after' => 'La fecha de fin debe ser una fecha posterior a la fecha de inicio.',
        ]);

        // ✅ Evitar duplicados (excluyendo el periodo actual)
        $existe = PeriodosBachillerato::where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('semestre_id', $this->semestre_id)
            ->where('id', '!=', $this->periodoId)
            ->exists();

        if ($existe) {
            $this->addError(
                'ciclo_escolar_id',
                'El periodo de bachillerato para la generación y semestre seleccionados ya existe en este ciclo escolar.'
            );
            return;
        }

        $periodo = PeriodosBachillerato::findOrFail($this->periodoId);

        $periodo->update([
            'generacion_id' => $this->generacion_id,
            'semestre_id' => $this->semestre_id,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'mes_id' => $this->mes_id,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
        ]);

        $this->dispatch('swal', [
            'title' => '¡Periodo bachillerato actualizado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshPeriodosBachillerato');
        $this->dispatch('cerrar-modal-editar');
        $this->cerrarModal();
    }

    public function cerrarModal(): void
    {
        $this->reset([
            'open',
            'periodoId',
            'generacion_id',
            'semestre_id',
            'ciclo_escolar_id',
            'mes_id',
            'fecha_inicio',
            'fecha_fin',
            'periodo_nombre',
        ]);

        $this->resetValidation();
    }

    public function render()
    {
        $meses = MesesBachillerato::orderBy('id')->get();
        $generaciones = Generacion::where('nivel_id', 4)->orderBy('anio_ingreso', 'asc')->get();
        $semestres = Semestre::orderBy('numero')->get();
        $ciclosEscolares = CicloEscolar::orderBy('inicio_anio', 'desc')->get();

        return view('livewire.periodo-bachillerato.editar-periodo-bachillerato', compact(
            'generaciones',
            'semestres',
            'ciclosEscolares',
            'meses'
        ));
    }
}
