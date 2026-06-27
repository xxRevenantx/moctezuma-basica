<?php

namespace App\Livewire\Accion;

use App\Models\CalificacionCampoFormativo;
use App\Models\CicloEscolar;
use App\Services\CalificacionOficialPrimariaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class CalificacionesCamposFormativos extends Component
{
    public int $nivelId;
    public int $cicloEscolarId;
    public int $generacionId;
    public int $gradoId;
    public int $grupoId;
    public int $periodoId;

    public array $campos = [];
    public array $alumnos = [];
    public array $calificacionesOficiales = [];
    public array $observaciones = [];
    public ?array $periodo = null;

    public function mount(
        int $nivelId,
        int $cicloEscolarId,
        int $generacionId,
        int $gradoId,
        int $grupoId,
        int $periodoId,
        CalificacionOficialPrimariaService $service,
    ): void {
        $this->nivelId = $nivelId;
        $this->cicloEscolarId = $cicloEscolarId;
        $this->generacionId = $generacionId;
        $this->gradoId = $gradoId;
        $this->grupoId = $grupoId;
        $this->periodoId = $periodoId;

        $this->cargar($service);
    }

    public function cargar(CalificacionOficialPrimariaService $service): void
    {
        $reporte = $service->capturaPeriodo(
            nivelId: $this->nivelId,
            cicloEscolarId: $this->cicloEscolarId,
            generacionId: $this->generacionId,
            gradoId: $this->gradoId,
            grupoId: $this->grupoId,
            periodoId: $this->periodoId,
        );

        $this->periodo = $reporte['periodo'];
        $this->campos = $reporte['campos']->map(fn ($campo) => [
            'id' => (int) $campo->id,
            'nombre' => $campo->nombre,
            'slug' => $campo->slug,
            'color_fondo' => $campo->color_fondo,
            'color_texto' => $campo->color_texto,
        ])->values()->all();
        $this->alumnos = $reporte['alumnos']->all();

        $this->calificacionesOficiales = [];
        $this->observaciones = [];

        foreach ($this->alumnos as $alumno) {
            foreach ($alumno['campos'] as $campoId => $celda) {
                $this->calificacionesOficiales[$alumno['inscripcion_id']][$campoId] = $celda['oficial'];
                $this->observaciones[$alumno['inscripcion_id']][$campoId] = '';
            }
        }
    }

    #[On('calificaciones-internas-guardadas')]
    public function recargarSugerencias(CalificacionOficialPrimariaService $service): void
    {
        $this->cargar($service);
    }

    public function aplicarSugerencias(): void
    {
        foreach ($this->alumnos as $alumno) {
            foreach ($alumno['campos'] as $campoId => $celda) {
                $actual = $this->calificacionesOficiales[$alumno['inscripcion_id']][$campoId] ?? null;

                if (($actual === null || $actual === '') && $celda['sugerencia_entera'] !== null) {
                    $this->calificacionesOficiales[$alumno['inscripcion_id']][$campoId] = $celda['sugerencia_entera'];
                }
            }
        }
    }

    public function guardar(CalificacionOficialPrimariaService $service): void
    {
        $ciclo = CicloEscolar::query()->findOrFail($this->cicloEscolarId);

        if ($ciclo->cerrado_at && ! auth()->user()?->is_admin) {
            $this->addError('calificacionesOficiales', 'El ciclo escolar está cerrado. Solo administración puede corregirlo.');
            return;
        }

        $reglas = [];

        foreach ($this->alumnos as $alumno) {
            foreach ($this->campos as $campo) {
                $reglas['calificacionesOficiales.' . $alumno['inscripcion_id'] . '.' . $campo['id']] = [
                    'nullable',
                    'integer',
                    'between:0,10',
                ];
                $reglas['observaciones.' . $alumno['inscripcion_id'] . '.' . $campo['id']] = [
                    'nullable',
                    'string',
                    'max:1000',
                ];
            }
        }

        $this->validate($reglas, [
            'calificacionesOficiales.*.*.integer' => 'La calificación oficial debe ser un número entero.',
            'calificacionesOficiales.*.*.between' => 'La calificación oficial debe estar entre 0 y 10.',
            'observaciones.*.*.max' => 'La observación no debe exceder 1000 caracteres.',
        ]);

        DB::transaction(function (): void {
            foreach ($this->alumnos as $alumno) {
                foreach ($alumno['campos'] as $campoId => $celda) {
                    $valor = $this->calificacionesOficiales[$alumno['inscripcion_id']][$campoId] ?? null;
                    $valor = ($valor === '' || $valor === null) ? null : (int) $valor;
                    $observacion = trim((string) ($this->observaciones[$alumno['inscripcion_id']][$campoId] ?? ''));

                    $condiciones = [
                        'periodo_id' => $this->periodoId,
                        'inscripcion_id' => (int) $alumno['inscripcion_id'],
                        'campo_formativo_id' => (int) $campoId,
                    ];

                    if ($valor === null) {
                        CalificacionCampoFormativo::query()->where($condiciones)->delete();
                        continue;
                    }

                    CalificacionCampoFormativo::query()->updateOrCreate(
                        $condiciones,
                        [
                            'trayectoria_academica_id' => $alumno['trayectoria_academica_id'],
                            'ciclo_escolar_id' => $this->cicloEscolarId,
                            'nivel_id' => $this->nivelId,
                            'grado_id' => $this->gradoId,
                            'grupo_id' => $this->grupoId,
                            'generacion_id' => $this->generacionId,
                            'calificacion_sugerida' => $celda['promedio_sugerido_preciso'],
                            'calificacion_oficial' => $valor,
                            'confirmada' => true,
                            'es_reconstruida' => false,
                            'observaciones' => $observacion !== '' ? $observacion : null,
                            'confirmada_por' => Auth::id(),
                            'confirmada_at' => now(),
                        ]
                    );
                }
            }
        });

        $this->cargar($service);

        $this->dispatch('swal', [
            'title' => 'Calificaciones oficiales guardadas',
            'text' => 'Las calificaciones por campo formativo quedaron confirmadas para este periodo.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function render()
    {
        return view('livewire.accion.calificaciones-campos-formativos');
    }
}
