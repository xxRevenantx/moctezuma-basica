<?php

namespace App\Livewire\Periodo;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\MesesBachillerato;
use App\Models\MesesBasica;
use App\Models\Nivel;
use App\Models\Parcial;
use App\Models\Periodos;
use App\Models\PeriodosBasica;
use App\Models\Semestre;
use Livewire\Component;

class CrearPeriodo extends Component
{
    public $nivel_id = null;
    public $generacion_id = null;
    public $semestre_id = null;
    public $ciclo_escolar_id = null;

    public $mes_bachillerato_id = null;
    public $parcial_bachillerato_id = null;

    public $mes_basica_id = null;
    public $periodo_basica_id = null;

    public $fecha_inicio = null;
    public $fecha_fin = null;

    /**
     * Se limpian los campos cuando cambia el nivel.
     */
    public function updatedNivelId($value): void
    {
        $this->reset([
            'generacion_id',
            'semestre_id',
            'mes_bachillerato_id',
            'parcial_bachillerato_id',
            'mes_basica_id',
            'periodo_basica_id',
        ]);

        $this->resetValidation([
            'generacion_id',
            'semestre_id',
            'mes_bachillerato_id',
            'parcial_bachillerato_id',
            'mes_basica_id',
            'periodo_basica_id',
        ]);
    }

    /**
     * Indica si el nivel seleccionado es bachillerato.
     */
    public function getEsBachilleratoProperty(): bool
    {
        return (int) $this->nivel_id === 4;
    }

    /**
     * Indica si el nivel seleccionado pertenece a básica.
     */
    public function getEsBasicaProperty(): bool
    {
        return !empty($this->nivel_id) && (int) $this->nivel_id !== 4;
    }

    protected function rules(): array
    {
        $rules = [
            'nivel_id' => 'required|exists:niveles,id',
            'ciclo_escolar_id' => 'required|exists:ciclo_escolares,id',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ];

        if ($this->esBachillerato) {
            $rules['generacion_id'] = 'required|exists:generaciones,id';
            $rules['semestre_id'] = 'required|exists:semestres,id';
            $rules['mes_bachillerato_id'] = 'required|exists:meses_bachilleratos,id';
            $rules['parcial_bachillerato_id'] = 'required|exists:parciales,id';

            $rules['mes_basica_id'] = 'nullable';
            $rules['periodo_basica_id'] = 'nullable';
        }

        if ($this->esBasica) {
            $rules['mes_basica_id'] = 'required|exists:meses_basica,id';
            $rules['periodo_basica_id'] = 'required|exists:periodos_basica,id';

            $rules['generacion_id'] = 'nullable';
            $rules['semestre_id'] = 'nullable';
            $rules['mes_bachillerato_id'] = 'nullable';
            $rules['parcial_bachillerato_id'] = 'nullable';
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

            'mes_bachillerato_id.required' => 'El mes de bachillerato es obligatorio.',
            'mes_bachillerato_id.exists' => 'El mes de bachillerato seleccionado no es válido.',

            'parcial_bachillerato_id.required' => 'El parcial es obligatorio para bachillerato.',
            'parcial_bachillerato_id.exists' => 'El parcial seleccionado no es válido.',

            'mes_basica_id.required' => 'El mes de básica es obligatorio.',
            'mes_basica_id.exists' => 'El mes de básica seleccionado no es válido.',

            'periodo_basica_id.required' => 'El periodo de básica es obligatorio.',
            'periodo_basica_id.exists' => 'El periodo de básica seleccionado no es válido.',

            'fecha_inicio.date' => 'La fecha de inicio no es válida.',
            'fecha_fin.date' => 'La fecha de fin no es válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }

    public function guardarPeriodo(): void
    {
        $this->validate();

        if ($this->esBachillerato) {
            $generacionValida = Generacion::query()
                ->where('id', $this->generacion_id)
                ->where('nivel_id', $this->nivel_id)
                ->exists();

            if (!$generacionValida) {
                $this->addError('generacion_id', 'La generación no pertenece al nivel seleccionado.');
                return;
            }

            $existe = Periodos::query()
                ->where('nivel_id', $this->nivel_id)
                ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                ->where('generacion_id', $this->generacion_id)
                ->where('semestre_id', $this->semestre_id)
                ->where('mes_bachillerato_id', $this->mes_bachillerato_id)
                ->where('parcial_bachillerato_id', $this->parcial_bachillerato_id)
                ->exists();

            if ($existe) {
                $this->addError('parcial_bachillerato_id', 'El periodo para este parcial ya existe.');
                return;
            }
        }

        if ($this->esBasica) {
            $existe = Periodos::query()
                ->where('nivel_id', $this->nivel_id)
                ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                ->where('mes_basica_id', $this->mes_basica_id)
                ->where('periodo_basica_id', $this->periodo_basica_id)
                ->exists();

            if ($existe) {
                $this->addError('periodo_basica_id', 'El periodo para este nivel ya existe.');
                return;
            }
        }

        Periodos::create([
            'nivel_id' => $this->nivel_id,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,

            'generacion_id' => $this->esBachillerato ? $this->generacion_id : null,
            'semestre_id' => $this->esBachillerato ? $this->semestre_id : null,
            'mes_bachillerato_id' => $this->esBachillerato ? $this->mes_bachillerato_id : null,
            'parcial_bachillerato_id' => $this->esBachillerato ? $this->parcial_bachillerato_id : null,

            'mes_basica_id' => $this->esBasica ? $this->mes_basica_id : null,
            'periodo_basica_id' => $this->esBasica ? $this->periodo_basica_id : null,

            'fecha_inicio' => $this->fecha_inicio ?: null,
            'fecha_fin' => $this->fecha_fin ?: null,
        ]);

        $this->dispatch('swal', [
            'title' => '¡Periodo creado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->reset([
            'nivel_id',
            'generacion_id',
            'semestre_id',
            'ciclo_escolar_id',
            'mes_bachillerato_id',
            'parcial_bachillerato_id',
            'mes_basica_id',
            'periodo_basica_id',
            'fecha_inicio',
            'fecha_fin',
        ]);

        $this->dispatch('refreshPeriodos');
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

        return view('livewire.periodo.crear-periodo', [
            'niveles' => Nivel::query()
                ->orderBy('nombre')
                ->get(),

            'generaciones' => $generaciones,

            'semestres' => Semestre::query()
                ->orderBy('numero')
                ->get(),

            'ciclosEscolares' => CicloEscolar::query()
                ->orderBy('inicio_anio', 'desc')
                ->get(),

            'mesesBachillerato' => MesesBachillerato::query()
                ->orderBy('id')
                ->get(),

            'parciales' => Parcial::query()
                ->orderBy('parcial')
                ->get(),

            'mesesBasica' => MesesBasica::query()
                ->orderBy('id')
                ->get(),

            'periodosBasica' => PeriodosBasica::query()
                ->orderBy('periodo')
                ->get(),
        ]);
    }
}
