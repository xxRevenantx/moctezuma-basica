<?php

namespace App\Livewire\Generacion;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Nivel;
use App\Services\AsignacionEscolarService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CrearGeneracion extends Component
{
    public ?int $nivel_id = null;
    public string $anio_ingreso = '';
    public string $anio_egreso = '';
    public string $nombre = '';
    public ?int $ciclo_escolar_inicio_id = null;
    public ?int $ciclo_escolar_fin_id = null;
    public ?string $fecha_inicio = null;
    public ?string $fecha_termino = null;
    public ?string $detalleDuracion = null;

    public function updatedNivelId(): void
    {
        $this->sincronizarGeneracion();
    }

    public function updatedAnioIngreso(mixed $valor): void
    {
        $this->anio_ingreso = $this->normalizarAnio($valor);
        $this->sincronizarGeneracion();
    }

    public function guardarGeneracion(AsignacionEscolarService $asignaciones): void
    {
        $this->anio_ingreso = $this->normalizarAnio($this->anio_ingreso);
        $this->sincronizarGeneracion($asignaciones, true);

        $data = $this->validate([
            'nivel_id' => ['required', Rule::exists('niveles', 'id')],
            'anio_ingreso' => $this->reglasAnioIngreso(),
            'anio_egreso' => $this->reglasAnioEgreso(),
            'nombre' => ['required', 'string', 'max:50'],
            'ciclo_escolar_inicio_id' => ['nullable', Rule::exists('ciclo_escolares', 'id')],
            'ciclo_escolar_fin_id' => ['nullable', Rule::exists('ciclo_escolares', 'id')],
            'fecha_inicio' => $this->reglasFechaInicio(),
            'fecha_termino' => $this->reglasFechaTermino(),
        ], $this->mensajesValidacion());

        $nivel = Nivel::query()->findOrFail((int) $data['nivel_id']);
        $anioIngreso = (int) $data['anio_ingreso'];
        $anioEgreso = (int) $data['anio_egreso'];
        $anioEgresoEsperado = $anioIngreso + $asignaciones->duracionNivel($nivel);

        if ($anioEgreso !== $anioEgresoEsperado) {
            $this->addError(
                'anio_egreso',
                "Para {$nivel->nombre}, la generación debe terminar en {$anioEgresoEsperado}."
            );
            return;
        }

        $existe = Generacion::query()
            ->where('nivel_id', $data['nivel_id'])
            ->where('anio_ingreso', $anioIngreso)
            ->where('anio_egreso', $anioEgreso)
            ->exists();

        if ($existe) {
            $this->addError('anio_ingreso', 'La generación ya existe en este nivel.');
            return;
        }

        $data['anio_ingreso'] = $anioIngreso;
        $data['anio_egreso'] = $anioEgreso;

        Generacion::query()->create($data + ['status' => true]);

        $this->reset();
        $this->dispatch('refreshGeneraciones');
        $this->dispatch('swal', [
            'title' => 'Generación creada',
            'text' => 'Los años y ciclos se calcularon según la duración oficial del nivel.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    private function sincronizarGeneracion(?AsignacionEscolarService $asignaciones = null, bool $preservarFechas = false): void
    {
        $this->resetValidation([
            'anio_ingreso',
            'anio_egreso',
            'ciclo_escolar_inicio_id',
            'ciclo_escolar_fin_id',
        ]);

        if (!$this->nivel_id || !$this->esAnioIngresoValido($this->anio_ingreso)) {
            $this->limpiarCamposCalculados();

            if (strlen($this->anio_ingreso) === 4 && (int) $this->anio_ingreso !== 0) {
                $this->addError('anio_ingreso', 'El año de ingreso debe estar entre 1900 y 2200.');
            }

            return;
        }

        $nivel = Nivel::query()->find($this->nivel_id);

        if (!$nivel) {
            $this->limpiarCamposCalculados();
            return;
        }

        $asignaciones ??= app(AsignacionEscolarService::class);
        $duracion = $asignaciones->duracionNivel($nivel);
        $anioIngreso = (int) $this->anio_ingreso;
        $anioEgreso = $anioIngreso + $duracion;

        $this->anio_egreso = (string) $anioEgreso;
        $this->nombre = $anioIngreso . '-' . $anioEgreso;
        if (!$preservarFechas || blank($this->fecha_inicio)) {
            $this->fecha_inicio = sprintf('%04d-08-01', $anioIngreso);
        }

        if (!$preservarFechas || blank($this->fecha_termino)) {
            $this->fecha_termino = sprintf('%04d-07-31', $anioEgreso);
        }
        $this->ciclo_escolar_inicio_id = CicloEscolar::query()
            ->where('inicio_anio', $anioIngreso)
            ->value('id');
        $this->ciclo_escolar_fin_id = CicloEscolar::query()
            ->where('fin_anio', $anioEgreso)
            ->value('id');
        $this->detalleDuracion = "Duración calculada: {$duracion} ciclos escolares.";
    }

    private function normalizarAnio(mixed $valor): string
    {
        $soloDigitos = preg_replace('/\D+/', '', (string) $valor) ?? '';

        return substr($soloDigitos, 0, 4);
    }

    private function esAnioIngresoValido(string $anio): bool
    {
        return preg_match('/^\d{4}$/', $anio) === 1
            && (int) $anio >= 1900
            && (int) $anio <= 2200;
    }

    private function reglasAnioIngreso(): array
    {
        return [
            'required',
            'string',
            'regex:/^\d{4}$/',
            function (string $attribute, mixed $value, \Closure $fail): void {
                $anio = (int) $value;

                if ($anio < 1900 || $anio > 2200) {
                    $fail('El año de ingreso debe estar entre 1900 y 2200.');
                }
            },
        ];
    }

    private function reglasAnioEgreso(): array
    {
        return [
            'required',
            'string',
            'regex:/^\d{4}$/',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if ((int) $value <= (int) $this->anio_ingreso) {
                    $fail('El año de egreso debe ser posterior al año de ingreso.');
                }
            },
        ];
    }


    private function reglasFechaInicio(): array
    {
        return [
            'required',
            'date_format:Y-m-d',
            function (string $attribute, mixed $value, \Closure $fail): void {
                $anioFecha = (int) substr((string) $value, 0, 4);

                if ($anioFecha !== (int) $this->anio_ingreso) {
                    $fail("La fecha de inicio debe pertenecer al año {$this->anio_ingreso}.");
                }
            },
        ];
    }

    private function reglasFechaTermino(): array
    {
        return [
            'required',
            'date_format:Y-m-d',
            'after:fecha_inicio',
            function (string $attribute, mixed $value, \Closure $fail): void {
                $anioFecha = (int) substr((string) $value, 0, 4);

                if ($anioFecha !== (int) $this->anio_egreso) {
                    $fail("La fecha de término debe pertenecer al año {$this->anio_egreso}.");
                }
            },
        ];
    }

    private function mensajesValidacion(): array
    {
        return [
            'anio_ingreso.required' => 'Captura el año de ingreso.',
            'anio_ingreso.regex' => 'El año de ingreso debe contener exactamente 4 dígitos.',
            'anio_egreso.required' => 'No se pudo calcular el año de egreso.',
            'anio_egreso.regex' => 'El año de egreso debe contener exactamente 4 dígitos.',
            'fecha_inicio.required' => 'Selecciona la fecha de inicio.',
            'fecha_inicio.date_format' => 'La fecha de inicio no tiene un formato válido.',
            'fecha_termino.required' => 'Selecciona la fecha de término.',
            'fecha_termino.date_format' => 'La fecha de término no tiene un formato válido.',
            'fecha_termino.after' => 'La fecha de término debe ser posterior a la fecha de inicio.',
        ];
    }

    private function limpiarCamposCalculados(): void
    {
        $this->anio_egreso = '';
        $this->nombre = '';
        $this->ciclo_escolar_inicio_id = null;
        $this->ciclo_escolar_fin_id = null;
        $this->fecha_inicio = null;
        $this->fecha_termino = null;
        $this->detalleDuracion = null;
    }

    public function render()
    {
        return view('livewire.generacion.crear-generacion', [
            'niveles' => Nivel::query()->orderBy('id')->get(),
            'ciclosEscolares' => CicloEscolar::query()->orderByDesc('inicio_anio')->get(),
        ]);
    }
}
