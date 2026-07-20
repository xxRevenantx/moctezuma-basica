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
use App\Services\AsignacionEscolarService;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarPeriodo extends Component
{
    public $periodo_id = null;

    public $nivel_id = null;
    public $generacion_id = null;
    public $semestre_id = null;
    public $ciclo_escolar_id = null;

    public $mes_basica_id = null;
    public $periodo_basica_id = null;

    public $mes_bachillerato_id = null;
    public $parcial_bachillerato_id = null;

    public $fecha_inicio = null;
    public $fecha_fin = null;

    public string $periodo_nombre = '';
    public bool $tiene_calificaciones = false;

    public function updatedCicloEscolarId(): void
    {
        $this->semestre_id = null;
        $this->resetValidation(['ciclo_escolar_id', 'generacion_id', 'semestre_id']);
    }

    public function updatedGeneracionId(): void
    {
        $this->semestre_id = null;
        $this->resetValidation(['generacion_id', 'semestre_id']);
    }

    public function getEsBachilleratoProperty(): bool
    {
        if (empty($this->nivel_id)) {
            return false;
        }

        return Nivel::query()
            ->whereKey($this->nivel_id)
            ->where('slug', 'bachillerato')
            ->exists();
    }

    public function getEsBasicaProperty(): bool
    {
        return !empty($this->nivel_id) && !$this->esBachillerato;
    }

    #[On('editarModal')]
    public function editarModal($id): void
    {
        $periodo = Periodos::query()
            ->with([
                'nivel',
                'generacion',
                'semestre',
                'cicloEscolar',
                'mesesBasica',
                'periodoBasica',
                'mesesBachillerato',
                'parcialBachillerato',
            ])
            ->find($id);

        if (!$periodo) {
            $this->dispatch('cerrar-modal-editar');

            $this->dispatch('swal', [
                'title' => 'Periodo no encontrado',
                'icon' => 'error',
                'position' => 'top-end',
            ]);

            return;
        }

        $this->resetValidation();

        $this->periodo_id = $periodo->id;
        $this->nivel_id = $periodo->nivel_id;
        $this->generacion_id = $periodo->generacion_id;
        $this->semestre_id = $periodo->semestre_id;
        $this->ciclo_escolar_id = $periodo->ciclo_escolar_id;

        $this->mes_basica_id = $periodo->mes_basica_id;
        $this->periodo_basica_id = $periodo->periodo_basica_id;

        $this->mes_bachillerato_id = $periodo->mes_bachillerato_id;
        $this->parcial_bachillerato_id = $periodo->parcial_bachillerato_id;

        $this->fecha_inicio = $periodo->fecha_inicio;
        $this->fecha_fin = $periodo->fecha_fin;

        $this->periodo_nombre = $this->obtenerNombrePeriodo($periodo);
        $this->tiene_calificaciones = $periodo->calificaciones()->exists();

        $this->dispatch('editar-cargado');
    }

    public function updatedNivelId(): void
    {
        $this->reset([
            'generacion_id',
            'semestre_id',
            'mes_basica_id',
            'periodo_basica_id',
            'mes_bachillerato_id',
            'parcial_bachillerato_id',
        ]);

        $this->resetValidation([
            'generacion_id',
            'semestre_id',
            'mes_basica_id',
            'periodo_basica_id',
            'mes_bachillerato_id',
            'parcial_bachillerato_id',
        ]);
    }

    protected function rules(): array
    {
        $rules = [
            'nivel_id' => 'required|exists:niveles,id',
            'ciclo_escolar_id' => 'required|exists:ciclo_escolares,id',
            'fecha_inicio' => 'nullable|required_with:fecha_fin|date',
            'fecha_fin' => 'nullable|required_with:fecha_inicio|date|after_or_equal:fecha_inicio',
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

            'parcial_bachillerato_id.required' => 'El parcial de bachillerato es obligatorio.',
            'parcial_bachillerato_id.exists' => 'El parcial seleccionado no es válido.',

            'mes_basica_id.required' => 'El mes de básica es obligatorio.',
            'mes_basica_id.exists' => 'El mes de básica seleccionado no es válido.',

            'periodo_basica_id.required' => 'El periodo de básica es obligatorio.',
            'periodo_basica_id.exists' => 'El periodo de básica seleccionado no es válido.',

            'fecha_inicio.required_with' => 'Captura también la fecha de inicio.',
            'fecha_fin.required_with' => 'Captura también la fecha de fin.',
            'fecha_inicio.date' => 'La fecha de inicio no es válida.',
            'fecha_fin.date' => 'La fecha de fin no es válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }

    public function actualizarPeriodo(): void
    {
        $this->validate();

        if (!$this->validarFechasDelCiclo()) {
            return;
        }

        $periodo = Periodos::find($this->periodo_id);

        if (!$periodo) {
            $this->dispatch('cerrar-modal-editar');

            $this->dispatch('swal', [
                'title' => 'Periodo no encontrado',
                'icon' => 'error',
                'position' => 'top-end',
            ]);

            return;
        }

        if ($periodo->calificaciones()->exists() && $this->identidadAcademicaModificada($periodo)) {
            $this->addError(
                'nivel_id',
                'Este periodo ya tiene calificaciones. Solo puedes modificar las fechas de inicio y fin.'
            );

            return;
        }

        if ($this->esBachillerato) {
            $generacionValida = Generacion::query()
                ->where('id', $this->generacion_id)
                ->where('nivel_id', $this->nivel_id)
                ->exists();

            if (!$generacionValida) {
                $this->addError('generacion_id', 'La generación no pertenece al nivel seleccionado.');
                return;
            }

            if (!$this->validarCompatibilidadBachillerato()) {
                return;
            }

            $existe = Periodos::query()
                ->where('id', '!=', $this->periodo_id)
                ->where('nivel_id', $this->nivel_id)
                ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                ->where('generacion_id', $this->generacion_id)
                ->where('semestre_id', $this->semestre_id)
                ->where('mes_bachillerato_id', $this->mes_bachillerato_id)
                ->where('parcial_bachillerato_id', $this->parcial_bachillerato_id)
                ->exists();

            if ($existe) {
                $this->addError('parcial_bachillerato_id', 'Ya existe un periodo con estos datos de bachillerato.');
                return;
            }
        }

        if ($this->esBasica) {
            $ordenMes = MesesBasica::query()->orderBy('id')->pluck('id')->values();
            $ordenPeriodo = PeriodosBasica::query()->orderBy('periodo')->pluck('id')->values();
            $posicionMes = $ordenMes->search((int) $this->mes_basica_id, true);
            $posicionPeriodo = $ordenPeriodo->search((int) $this->periodo_basica_id, true);

            if ($posicionMes === false || $posicionPeriodo === false || $posicionMes !== $posicionPeriodo) {
                $this->addError('periodo_basica_id', 'El mes de básica no corresponde al periodo seleccionado.');
                return;
            }

            $existe = Periodos::query()
                ->where('id', '!=', $this->periodo_id)
                ->where('nivel_id', $this->nivel_id)
                ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                ->where('mes_basica_id', $this->mes_basica_id)
                ->where('periodo_basica_id', $this->periodo_basica_id)
                ->exists();

            if ($existe) {
                $this->addError('periodo_basica_id', 'Ya existe un periodo de básica con estos datos.');
                return;
            }
        }

        $periodo->update([
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
            'nivel_id',
            'generacion_id',
            'semestre_id',
            'ciclo_escolar_id',
            'mes_basica_id',
            'periodo_basica_id',
            'mes_bachillerato_id',
            'parcial_bachillerato_id',
            'fecha_inicio',
            'fecha_fin',
            'periodo_nombre',
            'tiene_calificaciones',
        ]);

        $this->resetValidation();
    }

    private function validarFechasDelCiclo(): bool
    {
        if (!$this->fecha_inicio && !$this->fecha_fin) {
            return true;
        }

        $ciclo = CicloEscolar::find($this->ciclo_escolar_id);

        if (!$ciclo) {
            return false;
        }

        foreach (['fecha_inicio', 'fecha_fin'] as $campo) {
            if (!$this->{$campo}) {
                continue;
            }

            $anio = (int) Carbon::parse($this->{$campo})->format('Y');

            if ($anio < (int) $ciclo->inicio_anio || $anio > (int) $ciclo->fin_anio) {
                $this->addError(
                    $campo,
                    "La fecha debe pertenecer al ciclo escolar {$ciclo->inicio_anio}-{$ciclo->fin_anio}."
                );

                return false;
            }
        }

        return true;
    }

    private function identidadAcademicaModificada(Periodos $periodo): bool
    {
        $actual = [
            'nivel_id' => $this->enteroONulo($periodo->nivel_id),
            'ciclo_escolar_id' => $this->enteroONulo($periodo->ciclo_escolar_id),
            'generacion_id' => $this->enteroONulo($periodo->generacion_id),
            'semestre_id' => $this->enteroONulo($periodo->semestre_id),
            'mes_basica_id' => $this->enteroONulo($periodo->mes_basica_id),
            'periodo_basica_id' => $this->enteroONulo($periodo->periodo_basica_id),
            'mes_bachillerato_id' => $this->enteroONulo($periodo->mes_bachillerato_id),
            'parcial_bachillerato_id' => $this->enteroONulo($periodo->parcial_bachillerato_id),
        ];

        $nuevo = [
            'nivel_id' => $this->enteroONulo($this->nivel_id),
            'ciclo_escolar_id' => $this->enteroONulo($this->ciclo_escolar_id),
            'generacion_id' => $this->esBachillerato ? $this->enteroONulo($this->generacion_id) : null,
            'semestre_id' => $this->esBachillerato ? $this->enteroONulo($this->semestre_id) : null,
            'mes_basica_id' => $this->esBasica ? $this->enteroONulo($this->mes_basica_id) : null,
            'periodo_basica_id' => $this->esBasica ? $this->enteroONulo($this->periodo_basica_id) : null,
            'mes_bachillerato_id' => $this->esBachillerato ? $this->enteroONulo($this->mes_bachillerato_id) : null,
            'parcial_bachillerato_id' => $this->esBachillerato ? $this->enteroONulo($this->parcial_bachillerato_id) : null,
        ];

        return $actual !== $nuevo;
    }

    private function enteroONulo(mixed $valor): ?int
    {
        return filled($valor) ? (int) $valor : null;
    }

    private function obtenerNombrePeriodo(Periodos $periodo): string
    {
        if ($periodo->nivel?->slug === 'bachillerato') {
            $mes = $periodo->mesesBachillerato->meses ?? 'Sin mes';
            $parcial = $periodo->parcialBachillerato->descripcion ?? 'Sin parcial';

            return $mes . ' - ' . $parcial;
        }

        $mes = $periodo->mesesBasica->meses ?? 'Sin mes';
        $periodoBasica = $periodo->periodoBasica->descripcion ?? 'Sin periodo';

        return $mes . ' - ' . $periodoBasica;
    }

    private function validarCompatibilidadBachillerato(): bool
    {
        if (!$this->esBachillerato) {
            return true;
        }

        $ciclo = CicloEscolar::query()->find($this->ciclo_escolar_id);
        $generacion = Generacion::query()
            ->where('nivel_id', $this->nivel_id)
            ->find($this->generacion_id);

        if (!$ciclo || !$generacion) {
            return false;
        }

        $semestresPermitidos = app(AsignacionEscolarService::class)
            ->semestresPermitidos($ciclo, $generacion)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if (!$semestresPermitidos->contains((int) $this->semestre_id)) {
            $this->addError(
                'semestre_id',
                "El semestre no corresponde a la generación {$generacion->anio_ingreso}-{$generacion->anio_egreso} durante el ciclo {$ciclo->inicio_anio}-{$ciclo->fin_anio}."
            );

            return false;
        }

        return true;
    }

    public function render()
    {
        $cicloSeleccionado = $this->ciclo_escolar_id
            ? CicloEscolar::query()->find($this->ciclo_escolar_id)
            : null;

        $generaciones = Generacion::query()
            ->where(function ($query): void {
                $query->where('status', true)
                    ->when(
                        $this->generacion_id,
                        fn ($sub) => $sub->orWhere('generaciones.id', $this->generacion_id)
                    );
            })
            ->when($this->esBachillerato, function ($query) use ($cicloSeleccionado): void {
                $query->where('nivel_id', $this->nivel_id);

                if ($cicloSeleccionado) {
                    $query->where('anio_ingreso', '<=', $cicloSeleccionado->inicio_anio)
                        ->where('anio_egreso', '>', $cicloSeleccionado->inicio_anio);
                }
            }, function ($query): void {
                $query->whereRaw('1 = 0');
            })
            ->orderByDesc('anio_ingreso')
            ->get();

        $generacionSeleccionada = $this->generacion_id
            ? $generaciones->firstWhere('id', (int) $this->generacion_id)
            : null;

        $semestres = $cicloSeleccionado && $generacionSeleccionada
            ? app(AsignacionEscolarService::class)
                ->semestresPermitidos($cicloSeleccionado, $generacionSeleccionada)
            : collect();

        return view('livewire.periodo.editar-periodo', [
            'niveles' => Nivel::query()
                ->orderBy('nombre')
                ->get(),

            'generaciones' => $generaciones,

            'semestres' => $semestres,

            'ciclosEscolares' => CicloEscolar::query()
                ->orderBy('inicio_anio', 'desc')
                ->get(),

            'mesesBasica' => MesesBasica::query()
                ->orderBy('id')
                ->get(),

            'periodosBasica' => PeriodosBasica::query()
                ->orderBy('periodo')
                ->get(),

            'mesesBachillerato' => MesesBachillerato::query()
                ->orderBy('id')
                ->get(),

            'parciales' => Parcial::query()
                ->orderBy('parcial')
                ->get(),
        ]);
    }
}
