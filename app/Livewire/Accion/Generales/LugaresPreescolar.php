<?php

namespace App\Livewire\Accion\Generales;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\LugarPreescolar;
use App\Models\Nivel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

use Carbon\Carbon;

class LugaresPreescolar extends Component
{
    public string $slug_nivel = '';

    public string $fecha_pdf = '';

    public $nivel;

    public Collection $cicloEscolares;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $grupos;

    public string $ciclo_escolar_id = '';
    public string $generacion_id = '';
    public string $grado_id = '';
    public string $grupo_id = '';

    public string $tipo_reconocimiento = 'periodo';
    public string $periodo = '1';

    public array $lugares = [];
    public array $motivos = [];



    public function mount(string $slug_nivel): void
    {
        $this->fecha_pdf = now()->format('Y-m-d');

        $this->slug_nivel = $slug_nivel;

        $this->nivel = Nivel::query()
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        if ($this->nivel->slug !== 'preescolar') {
            abort(404);
        }

        $this->cicloEscolares = CicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->orderByDesc('id')
            ->get(['id', 'inicio_anio', 'fin_anio']);

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('status', true)
            ->orderByDesc('anio_ingreso')
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso']);

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);

        $this->ciclo_escolar_id = (string) ($this->cicloEscolares->first()?->id ?? '');

        $this->cargarGrupos();
        $this->cargarAsignaciones();
    }

    public function updatedGeneracionId(): void
    {
        $this->grupo_id = '';
        $this->cargarGrupos();
        $this->cargarAsignaciones();
    }

    public function updatedGradoId(): void
    {
        $this->grupo_id = '';
        $this->cargarGrupos();
        $this->cargarAsignaciones();
    }

    public function updatedGrupoId(): void
    {
        $this->cargarAsignaciones();
    }

    public function updatedCicloEscolarId(): void
    {
        $this->cargarAsignaciones();
    }

    public function updatedTipoReconocimiento(): void
    {
        if ($this->tipo_reconocimiento === 'anual') {
            $this->periodo = '0';
        } else {
            $this->periodo = '1';
        }

        $this->cargarAsignaciones();
    }

    public function updatedPeriodo(): void
    {
        $this->cargarAsignaciones();
    }

    public function limpiarFiltros(): void
    {
        $this->ciclo_escolar_id = (string) ($this->cicloEscolares->first()?->id ?? '');
        $this->generacion_id = '';
        $this->grado_id = '';
        $this->grupo_id = '';
        $this->tipo_reconocimiento = 'periodo';
        $this->periodo = '1';

        $this->cargarGrupos();
        $this->cargarAsignaciones();
    }

    public function getAlumnosProperty(): Collection
    {
        if ($this->ciclo_escolar_id === '') {
            return collect();
        }

        return Inscripcion::query()
            ->with([
                'grado:id,nombre,orden',
                'grupo.asignacionGrupo:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
            ])
            ->where('nivel_id', $this->nivel->id)
            ->where('activo', true)
            ->whereNull('deleted_at')
            ->when($this->generacion_id !== '', fn($q) => $q->where('generacion_id', $this->generacion_id))
            ->when($this->grado_id !== '', fn($q) => $q->where('grado_id', $this->grado_id))
            ->when($this->grupo_id !== '', fn($q) => $q->where('grupo_id', $this->grupo_id))
            ->orderBy('grado_id')
            ->orderBy('grupo_id')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();
    }

    public function guardarAlumno(int $inscripcionId): void
    {
        $this->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'tipo_reconocimiento' => ['required', 'in:periodo,anual'],
            'periodo' => ['required', 'integer', 'in:0,1,2,3'],
            "lugares.{$inscripcionId}" => ['nullable', 'integer', 'in:1,2,3'],
            "motivos.{$inscripcionId}" => ['nullable', 'string', 'max:1000'],
        ]);

        $alumno = Inscripcion::query()
            ->where('id', $inscripcionId)
            ->where('nivel_id', $this->nivel->id)
            ->where('activo', true)
            ->firstOrFail();

        $tipo = $this->tipo_reconocimiento;
        $periodo = $tipo === 'anual' ? 0 : (int) $this->periodo;

        $lugar = isset($this->lugares[$inscripcionId]) && $this->lugares[$inscripcionId] !== ''
            ? (int) $this->lugares[$inscripcionId]
            : null;

        $motivo = trim((string) ($this->motivos[$inscripcionId] ?? ''));

        if ($motivo === '') {
            $motivo = $this->motivoDefault($lugar, $tipo);
        }

        LugarPreescolar::query()->updateOrCreate(
            [
                'inscripcion_id' => $alumno->id,
                'ciclo_escolar_id' => (int) $this->ciclo_escolar_id,
                'tipo_reconocimiento' => $tipo,
                'periodo' => $periodo,
            ],
            [
                'nivel_id' => $alumno->nivel_id,
                'grado_id' => $alumno->grado_id,
                'grupo_id' => $alumno->grupo_id,
                'generacion_id' => $alumno->generacion_id,
                'lugar' => $lugar,
                'texto_lugar' => $this->textoLugar($lugar),
                'motivo' => $motivo,
                'asignado_por' => Auth::id(),
                'fecha_asignacion' => now(),
            ]
        );

        $this->cargarAsignaciones();

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Reconocimiento guardado',
            'position' => 'top',
        ]);
    }

    public function eliminarAlumno(int $inscripcionId): void
    {
        $tipo = $this->tipo_reconocimiento;
        $periodo = $tipo === 'anual' ? 0 : (int) $this->periodo;

        LugarPreescolar::query()
            ->where('inscripcion_id', $inscripcionId)
            ->where('ciclo_escolar_id', (int) $this->ciclo_escolar_id)
            ->where('tipo_reconocimiento', $tipo)
            ->where('periodo', $periodo)
            ->delete();

        unset($this->lugares[$inscripcionId], $this->motivos[$inscripcionId]);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Reconocimiento eliminado',
            'position' => 'top',
        ]);
    }

    private function cargarGrupos(): void
    {
        $this->grupos = Grupo::query()
            ->with(['asignacionGrupo:id,nombre', 'grado:id,nombre,orden'])
            ->where('nivel_id', $this->nivel->id)
            ->when($this->generacion_id !== '', fn($q) => $q->where('generacion_id', $this->generacion_id))
            ->when($this->grado_id !== '', fn($q) => $q->where('grado_id', $this->grado_id))
            ->orderBy('grado_id')
            ->orderBy('asignacion_grupo_id')
            ->get(['id', 'asignacion_grupo_id', 'nivel_id', 'grado_id', 'generacion_id']);
    }

    private function cargarAsignaciones(): void
    {
        if ($this->ciclo_escolar_id === '') {
            $this->lugares = [];
            $this->motivos = [];
            return;
        }

        $tipo = $this->tipo_reconocimiento;
        $periodo = $tipo === 'anual' ? 0 : (int) $this->periodo;

        $ids = $this->alumnos->pluck('id');

        $registros = LugarPreescolar::query()
            ->whereIn('inscripcion_id', $ids)
            ->where('ciclo_escolar_id', (int) $this->ciclo_escolar_id)
            ->where('tipo_reconocimiento', $tipo)
            ->where('periodo', $periodo)
            ->get()
            ->keyBy('inscripcion_id');

        $this->lugares = [];
        $this->motivos = [];

        foreach ($this->alumnos as $alumno) {
            $registro = $registros->get($alumno->id);

            $this->lugares[$alumno->id] = $registro?->lugar ? (string) $registro->lugar : '';
            $this->motivos[$alumno->id] = $registro?->motivo ?? '';
        }
    }

    public function urlPdf(int $inscripcionId): ?string
    {
        $tipo = $this->tipo_reconocimiento;
        $periodo = $tipo === 'anual' ? 0 : (int) $this->periodo;

        $registro = LugarPreescolar::query()
            ->where('inscripcion_id', $inscripcionId)
            ->where('ciclo_escolar_id', (int) $this->ciclo_escolar_id)
            ->where('tipo_reconocimiento', $tipo)
            ->where('periodo', $periodo)
            ->first();

        if (!$registro) {
            return null;
        }

        return route('misrutas.lugares-preescolar.pdf', [
            'lugarPreescolar' => $registro->id,
            'fecha' => $this->fecha_pdf ?: now()->format('Y-m-d'),
        ]);
    }

    public function urlDiploma(int $inscripcionId): ?string
    {
        /*
        |--------------------------------------------------------------------------
        | El diploma solo se muestra en 3er periodo
        |--------------------------------------------------------------------------
        | No aplica para 1er periodo, 2do periodo ni reconocimiento anual.
        */
        if ($this->tipo_reconocimiento !== 'periodo' || (int) $this->periodo !== 3) {
            return null;
        }

        $registro = LugarPreescolar::query()
            ->where('inscripcion_id', $inscripcionId)
            ->where('ciclo_escolar_id', (int) $this->ciclo_escolar_id)
            ->where('tipo_reconocimiento', 'periodo')
            ->where('periodo', 3)
            ->first();

        if (!$registro) {
            return null;
        }

        return route('misrutas.lugares-preescolar.diploma', [
            'lugarPreescolar' => $registro->id,
            'fecha' => $this->fecha_pdf ?: now()->format('Y-m-d'),
        ]);
    }

    private function textoLugar(?int $lugar): ?string
    {
        return match ($lugar) {
            1 => 'Primer lugar',
            2 => 'Segundo lugar',
            3 => 'Tercer lugar',
            default => null,
        };
    }

    private function motivoDefault(?int $lugar, string $tipo): string
    {
        $periodoTexto = $tipo === 'anual'
            ? 'durante el ciclo escolar'
            : 'durante el periodo evaluado';

        return match ($lugar) {
            1 => "Por obtener el primer lugar en aprovechamiento y desarrollo integral {$periodoTexto}.",
            2 => "Por obtener el segundo lugar en aprovechamiento y desarrollo integral {$periodoTexto}.",
            3 => "Por obtener el tercer lugar en aprovechamiento y desarrollo integral {$periodoTexto}.",
            default => "Por su participación, esfuerzo y desarrollo integral {$periodoTexto}.",
        };
    }

    public function render()
    {
        return view('livewire.accion.generales.lugares-preescolar', [
            'alumnos' => $this->alumnos,
        ]);
    }
}
