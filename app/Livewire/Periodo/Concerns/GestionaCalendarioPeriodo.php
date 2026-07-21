<?php

namespace App\Livewire\Periodo\Concerns;

use App\Models\CicloEscolar;
use App\Models\Periodos;
use App\Services\PeriodoCalendarioService;
use Carbon\Carbon;

trait GestionaCalendarioPeriodo
{
    public ?string $fecha_evaluacion_inicio = null;
    public ?string $fecha_evaluacion_fin = null;
    public ?string $fecha_captura_inicio = null;
    public ?string $fecha_captura_fin = null;
    public bool $confirmar_traslape = false;
    public string $motivo_traslape = '';

    /** @var array<int, string> */
    public array $traslapesDetectados = [];

    protected function reglasCalendario(): array
    {
        return [
            'fecha_evaluacion_inicio' => ['nullable', 'required_with:fecha_evaluacion_fin', 'date'],
            'fecha_evaluacion_fin' => ['nullable', 'required_with:fecha_evaluacion_inicio', 'date', 'after_or_equal:fecha_evaluacion_inicio'],
            'fecha_captura_inicio' => ['nullable', 'required_with:fecha_captura_fin', 'date'],
            'fecha_captura_fin' => ['nullable', 'required_with:fecha_captura_inicio', 'date', 'after_or_equal:fecha_captura_inicio'],
            'confirmar_traslape' => ['boolean'],
            'motivo_traslape' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function mensajesCalendario(): array
    {
        return [
            'fecha_evaluacion_inicio.required_with' => 'Captura también el inicio de la evaluación.',
            'fecha_evaluacion_fin.required_with' => 'Captura también el fin de la evaluación.',
            'fecha_evaluacion_fin.after_or_equal' => 'El fin de evaluación debe ser igual o posterior al inicio.',
            'fecha_captura_inicio.required_with' => 'Captura también el inicio de la ventana de captura.',
            'fecha_captura_fin.required_with' => 'Captura también el fin de la ventana de captura.',
            'fecha_captura_fin.after_or_equal' => 'El fin de captura debe ser igual o posterior al inicio.',
        ];
    }

    protected function cargarCalendarioPeriodo(Periodos $periodo): void
    {
        $this->fecha_evaluacion_inicio = $periodo->fecha_evaluacion_inicio
            ? Carbon::parse($periodo->fecha_evaluacion_inicio)->format('Y-m-d')
            : ($periodo->fecha_inicio ? Carbon::parse($periodo->fecha_inicio)->format('Y-m-d') : null);
        $this->fecha_evaluacion_fin = $periodo->fecha_evaluacion_fin
            ? Carbon::parse($periodo->fecha_evaluacion_fin)->format('Y-m-d')
            : ($periodo->fecha_fin ? Carbon::parse($periodo->fecha_fin)->format('Y-m-d') : null);
        $this->fecha_captura_inicio = $periodo->fecha_captura_inicio
            ? Carbon::parse($periodo->fecha_captura_inicio)->format('Y-m-d')
            : null;
        $this->fecha_captura_fin = $periodo->fecha_captura_fin
            ? Carbon::parse($periodo->fecha_captura_fin)->format('Y-m-d')
            : null;
        $this->confirmar_traslape = (bool) $periodo->traslape_confirmado;
        $this->motivo_traslape = (string) ($periodo->motivo_traslape ?? '');
        $this->sincronizarFechasLegacy();
        $this->traslapesDetectados = [];
    }

    protected function sincronizarFechasLegacy(): void
    {
        // Se conservan las columnas heredadas para documentos e importaciones existentes.
        $this->fecha_inicio = $this->fecha_evaluacion_inicio;
        $this->fecha_fin = $this->fecha_evaluacion_fin;
    }

    protected function datosCalendarioPeriodo(): array
    {
        $this->sincronizarFechasLegacy();

        return [
            'fecha_inicio' => $this->fecha_evaluacion_inicio ?: null,
            'fecha_fin' => $this->fecha_evaluacion_fin ?: null,
            'fecha_evaluacion_inicio' => $this->fecha_evaluacion_inicio ?: null,
            'fecha_evaluacion_fin' => $this->fecha_evaluacion_fin ?: null,
            'fecha_captura_inicio' => $this->fecha_captura_inicio ?: null,
            'fecha_captura_fin' => $this->fecha_captura_fin ?: null,
            'traslape_confirmado' => $this->confirmar_traslape,
            'motivo_traslape' => $this->confirmar_traslape ? trim($this->motivo_traslape) : null,
        ];
    }

    protected function validarCalendarioPeriodo(PeriodoCalendarioService $service, ?int $ignorarId = null): bool
    {
        $this->sincronizarFechasLegacy();

        if (!$this->validarRangosDentroDelCiclo()) {
            return false;
        }

        $traslapes = $service->traslapes([
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'nivel_id' => $this->nivel_id,
            'generacion_id' => $this->esBachillerato ? $this->generacion_id : null,
            'semestre_id' => $this->esBachillerato ? $this->semestre_id : null,
            'fecha_evaluacion_inicio' => $this->fecha_evaluacion_inicio,
            'fecha_evaluacion_fin' => $this->fecha_evaluacion_fin,
        ], $ignorarId);

        $this->traslapesDetectados = $traslapes
            ->map(fn (Periodos $periodo): string => $service->etiqueta($periodo))
            ->values()
            ->all();

        if ($traslapes->isEmpty()) {
            $this->confirmar_traslape = false;
            $this->motivo_traslape = '';
            return true;
        }

        if (!$this->confirmar_traslape) {
            $this->addError(
                'confirmar_traslape',
                'Las fechas de evaluación se cruzan con otro periodo. Puedes continuar después de revisar y confirmar el traslape.'
            );
            return false;
        }

        if (mb_strlen(trim($this->motivo_traslape)) < 10) {
            $this->addError('motivo_traslape', 'Describe por qué este traslape es válido (mínimo 10 caracteres).');
            return false;
        }

        return true;
    }

    private function validarRangosDentroDelCiclo(): bool
    {
        $ciclo = CicloEscolar::query()->find($this->ciclo_escolar_id);

        if (!$ciclo) {
            return false;
        }

        $inicioCiclo = Carbon::create((int) $ciclo->inicio_anio, 7, 1)->startOfDay();
        $finCiclo = Carbon::create((int) $ciclo->fin_anio, 8, 31)->endOfDay();

        foreach ([
            'fecha_evaluacion_inicio', 'fecha_evaluacion_fin',
            'fecha_captura_inicio', 'fecha_captura_fin',
        ] as $campo) {
            if (!$this->{$campo}) {
                continue;
            }

            $fecha = Carbon::parse($this->{$campo});
            if ($fecha->lt($inicioCiclo) || $fecha->gt($finCiclo)) {
                $this->addError($campo, "La fecha debe pertenecer al ciclo {$ciclo->nombre}.");
                return false;
            }
        }

        return true;
    }

    protected function limpiarCalendarioPeriodo(): void
    {
        $this->reset([
            'fecha_evaluacion_inicio', 'fecha_evaluacion_fin',
            'fecha_captura_inicio', 'fecha_captura_fin',
            'confirmar_traslape', 'motivo_traslape', 'traslapesDetectados',
        ]);
    }
}
