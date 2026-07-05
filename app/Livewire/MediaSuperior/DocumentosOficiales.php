<?php

namespace App\Livewire\MediaSuperior;

use App\Models\AsistenciaFinalBachillerato;
use App\Models\EmisionDocumentoMediaSuperior;
use App\Services\MediaSuperior\DocumentosOficialesService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class DocumentosOficiales extends Component
{
    use WithFileUploads;

    #[Url(as: 'modulo')]
    public string $modulo = 'inicio';

    public string $ciclo_escolar_id = '';
    public string $generacion_id = '';
    public string $semestre_id = '';
    public string $grupo_id = '';
    public string $asignacion_materia_id = '';
    public string $inscripcion_id = '';
    public string $estatus = 'todos';
    public string $buscar_alumno = '';
    public string $modalidad_certificado = 'parcial';
    public string $formato_zip = 'pdf';
    public string $fecha_documento = '';

    /** @var array<int, string|int|float|null> */
    public array $asistencias = [];

    public $archivo_asistencias;

    public function mount(?string $modulo = null): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        if ($modulo) {
            $this->modulo = $modulo;
        }

        $actual = $this->service()->ciclos()->firstWhere('es_actual', true)
            ?: $this->service()->ciclos()->first();

        $this->ciclo_escolar_id = (string) ($actual?->id ?? '');
        $this->fecha_documento = now()->format('Y-m-d');
    }

    public function seleccionarModulo(string $modulo): void
    {
        $permitidos = ['inicio', 'registro-escolaridad', 'acta-resultados', 'kardex', 'certificado'];
        $this->modulo = in_array($modulo, $permitidos, true) ? $modulo : 'inicio';
        $this->resetErrorBag();
        $this->dispatch('documento-oficial-modulo-cambiado');
    }

    public function updatedCicloEscolarId(): void
    {
        $this->resetContextoDesde('generacion');
    }

    public function updatedGeneracionId(): void
    {
        $this->resetContextoDesde('semestre');
    }

    public function updatedSemestreId(): void
    {
        $this->resetContextoDesde('grupo');
    }

    public function updatedGrupoId(): void
    {
        $this->asignacion_materia_id = '';
        $this->inscripcion_id = '';
        $this->asistencias = [];
    }

    public function updatedAsignacionMateriaId(): void
    {
        $this->cargarAsistencias();
    }

    public function updatedEstatus(): void
    {
        if ($this->modulo === 'acta-resultados' && filled($this->asignacion_materia_id)) {
            $this->cargarAsistencias();
        }
    }

    #[Computed]
    public function ciclos(): Collection
    {
        return $this->service()->ciclos();
    }

    #[Computed]
    public function generaciones(): Collection
    {
        $ciclo = in_array($this->modulo, ['registro-escolaridad', 'acta-resultados'], true)
            ? $this->entero($this->ciclo_escolar_id)
            : null;

        return $this->service()->generaciones($ciclo);
    }

    #[Computed]
    public function semestres(): Collection
    {
        return $this->service()->semestres(
            $this->entero($this->generacion_id),
            $this->entero($this->ciclo_escolar_id),
        );
    }

    #[Computed]
    public function grupos(): Collection
    {
        $ciclo = in_array($this->modulo, ['registro-escolaridad', 'acta-resultados'], true)
            ? $this->entero($this->ciclo_escolar_id)
            : null;

        return $this->service()->grupos(
            $this->entero($this->generacion_id),
            $this->entero($this->semestre_id),
            $ciclo,
        );
    }

    #[Computed]
    public function asignaciones(): Collection
    {
        if (! $this->contextoGrupoCompleto()) {
            return collect();
        }

        return $this->service()->asignaciones(
            (int) $this->ciclo_escolar_id,
            (int) $this->grupo_id,
            (int) $this->semestre_id,
            true,
        );
    }

    #[Computed]
    public function alumnos(): Collection
    {
        if (blank($this->generacion_id)) {
            return collect();
        }

        return $this->service()->alumnos(
            (int) $this->generacion_id,
            $this->entero($this->grupo_id),
            $this->buscar_alumno,
        );
    }

    #[Computed]
    public function alumnosActa(): Collection
    {
        if (! $this->contextoGrupoCompleto() || blank($this->asignacion_materia_id)) {
            return collect();
        }

        return $this->service()->alumnosActa($this->filtros());
    }

    #[Computed]
    public function emisionesRecientes(): Collection
    {
        return EmisionDocumentoMediaSuperior::query()
            ->with(['inscripcion:id,matricula,nombre,apellido_paterno,apellido_materno', 'usuario:id,name'])
            ->where('nivel_id', $this->service()->nivel()->id)
            ->orderByDesc('emitido_at')
            ->limit(25)
            ->get();
    }

    #[Computed]
    public function vistaPrevia(): ?array
    {
        try {
            return match ($this->modulo) {
                'registro-escolaridad' => $this->contextoGrupoCompleto()
                    ? $this->service()->registroEscolaridad($this->filtros())
                    : null,
                'acta-resultados' => $this->contextoGrupoCompleto() && filled($this->asignacion_materia_id)
                    ? $this->service()->actaResultados($this->filtros())
                    : null,
                'kardex' => filled($this->inscripcion_id)
                    ? $this->service()->kardex((int) $this->inscripcion_id)
                    : null,
                'certificado' => filled($this->inscripcion_id)
                    ? $this->service()->certificado((int) $this->inscripcion_id, $this->modalidad_certificado)
                    : null,
                default => null,
            };
        } catch (Throwable $exception) {
            return [
                'error' => $exception->getMessage(),
            ];
        }
    }

    public function guardarAsistencias(): void
    {
        $this->validarContextoActa();

        $reglas = [];
        foreach ($this->asistencias as $inscripcionId => $valor) {
            $reglas["asistencias.$inscripcionId"] = ['nullable', 'numeric', 'min:0', 'max:100'];
        }
        $this->validate($reglas);

        foreach ($this->asistencias as $inscripcionId => $valor) {
            AsistenciaFinalBachillerato::query()->updateOrCreate(
                [
                    'inscripcion_id' => (int) $inscripcionId,
                    'asignacion_materia_id' => (int) $this->asignacion_materia_id,
                    'ciclo_escolar_id' => (int) $this->ciclo_escolar_id,
                ],
                [
                    'porcentaje' => $valor === '' || $valor === null ? null : (float) $valor,
                    'capturado_por' => Auth::id(),
                    'capturado_at' => now(),
                ],
            );
        }

        unset($this->vistaPrevia);
        $this->dispatch('swal', icon: 'success', title: 'Asistencia guardada', text: 'Los porcentajes se actualizaron correctamente.');
    }

    public function importarAsistencias(): void
    {
        $this->validarContextoActa();
        $this->validate([
            'archivo_asistencias' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            $hoja = IOFactory::load($this->archivo_asistencias->getRealPath())->getActiveSheet();
            $actualizadas = 0;

            for ($fila = 2; $fila <= $hoja->getHighestDataRow(); $fila++) {
                $matricula = trim((string) $hoja->getCell("A{$fila}")->getValue());
                $porcentaje = $hoja->getCell("C{$fila}")->getValue();

                if ($matricula === '' || $porcentaje === null || $porcentaje === '') {
                    continue;
                }

                $alumno = $this->alumnosActa->firstWhere('matricula', $matricula);
                if (! $alumno || ! is_numeric($porcentaje) || (float) $porcentaje < 0 || (float) $porcentaje > 100) {
                    continue;
                }

                AsistenciaFinalBachillerato::query()->updateOrCreate(
                    [
                        'inscripcion_id' => $alumno->id,
                        'asignacion_materia_id' => (int) $this->asignacion_materia_id,
                        'ciclo_escolar_id' => (int) $this->ciclo_escolar_id,
                    ],
                    [
                        'porcentaje' => (float) $porcentaje,
                        'capturado_por' => Auth::id(),
                        'capturado_at' => now(),
                    ],
                );
                $actualizadas++;
            }

            $this->archivo_asistencias = null;
            $this->cargarAsistencias();
            unset($this->vistaPrevia);
            $this->dispatch('swal', icon: 'success', title: 'Importación terminada', text: "$actualizadas asistencia(s) actualizadas.");
        } catch (Throwable $exception) {
            $this->addError('archivo_asistencias', 'No se pudo leer el archivo: ' . $exception->getMessage());
        }
    }

    public function cargarAsistencias(): void
    {
        $this->asistencias = [];

        if (! $this->contextoGrupoCompleto() || blank($this->asignacion_materia_id)) {
            return;
        }

        $existentes = AsistenciaFinalBachillerato::query()
            ->where('ciclo_escolar_id', (int) $this->ciclo_escolar_id)
            ->where('asignacion_materia_id', (int) $this->asignacion_materia_id)
            ->get()
            ->keyBy('inscripcion_id');

        foreach ($this->alumnosActa as $alumno) {
            $this->asistencias[$alumno->id] = optional($existentes->get($alumno->id))->porcentaje;
        }
    }

    public function filtros(): array
    {
        return [
            'ciclo_escolar_id' => $this->entero($this->ciclo_escolar_id),
            'generacion_id' => $this->entero($this->generacion_id),
            'semestre_id' => $this->entero($this->semestre_id),
            'grupo_id' => $this->entero($this->grupo_id),
            'asignacion_materia_id' => $this->entero($this->asignacion_materia_id),
            'inscripcion_id' => $this->entero($this->inscripcion_id),
            'estatus' => $this->estatus,
            'modalidad' => $this->modalidad_certificado,
            'fecha_documento' => $this->fecha_documento,
        ];
    }

    public function queryDescarga(): array
    {
        return array_filter($this->filtros(), fn ($valor) => $valor !== null && $valor !== '');
    }

    public function render()
    {
        return view('livewire.media-superior.documentos-oficiales');
    }

    private function service(): DocumentosOficialesService
    {
        return app(DocumentosOficialesService::class);
    }

    private function contextoGrupoCompleto(): bool
    {
        return filled($this->ciclo_escolar_id)
            && filled($this->generacion_id)
            && filled($this->semestre_id)
            && filled($this->grupo_id);
    }

    private function validarContextoActa(): void
    {
        $this->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'generacion_id' => ['required', 'integer', 'exists:generaciones,id'],
            'semestre_id' => ['required', 'integer', 'exists:semestres,id'],
            'grupo_id' => ['required', 'integer', 'exists:grupos,id'],
            'asignacion_materia_id' => ['required', 'integer', 'exists:asignacion_materias,id'],
        ]);
    }

    private function resetContextoDesde(string $desde): void
    {
        $orden = ['generacion', 'semestre', 'grupo', 'asignacion'];
        $indice = array_search($desde, $orden, true);

        if ($indice === false) {
            return;
        }

        foreach (array_slice($orden, $indice) as $campo) {
            $propiedad = match ($campo) {
                'generacion' => 'generacion_id',
                'semestre' => 'semestre_id',
                'grupo' => 'grupo_id',
                default => 'asignacion_materia_id',
            };
            $this->{$propiedad} = '';
        }

        $this->inscripcion_id = '';
        $this->asistencias = [];
    }

    private function entero(string|int|null $valor): ?int
    {
        return filled($valor) ? (int) $valor : null;
    }
}
