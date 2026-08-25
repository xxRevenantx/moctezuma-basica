<?php

namespace App\Livewire\Accion;

use App\Models\AsignacionMateria;
use App\Models\CicloEscolar;
use App\Models\Dia;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Horario as HorarioModel;
use App\Models\HorarioDocenteConfiguracion;
use App\Models\Materia;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Models\TallerSesion;
use App\Services\CicloNivelGateService;
use App\Services\ContextoEscolarService;
use App\Services\GroqHorarioService;
use App\Services\ContextoCicloEscolarSesion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

use App\Exports\HorarioExport;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

use App\Livewire\Accion\Concerns\GestionaContextoHorario;
use App\Livewire\Accion\Concerns\GestionaCapturaHorario;
use App\Livewire\Accion\Concerns\GestionaConflictosHorario;
use App\Livewire\Accion\Concerns\ProveeAnaliticaHorario;
use App\Livewire\Accion\Concerns\GestionaExportacionHorario;

class Horario extends Component
{
    use GestionaContextoHorario;
    use GestionaCapturaHorario;
    use GestionaConflictosHorario;
    use ProveeAnaliticaHorario;
    use GestionaExportacionHorario;

    public string $mensajeActualizacionHorario = '';
    public string $slug_nivel;
    public bool $mostrarSelectorNiveles = true;
    public bool $mostrarPlanificador = true;
    public bool $mostrarTalleres = true;

    public ?Nivel $nivel = null;

    public Collection $niveles;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $grupos;
    public Collection $horas;
    public Collection $dias;
    public Collection $semestres;
    public Collection $materiasDisponibles;
    public Collection $horariosGuardados;
    public Collection $talleresGuardados;
    public Collection $ciclosEscolares;

    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $grupo_id = null;
    public ?int $semestre_id = null;
    public ?int $ciclo_escolar_id = null;

    public bool $esBachillerato = false;

    public array $seleccionesHorario = [];

    public bool $mostrarModalTraslapeProfesor = false;
    public string $motivoSesionCompartida = 'Sesión compartida entre varios grados o grupos.';

    public array $pendienteHorario = [
        'hora_id' => null,
        'dia_id' => null,
        'asignacion_materia_id' => null,
        'clave_celda' => null,
    ];

    public array $conflictosProfesor = [];

    /** @var array<int, array<string, mixed>> */
    public array $alternativasConflicto = [];

    /** @var array<string, mixed>|null */
    public ?array $analisisConflictoIa = null;

    public string $tipoAnalisisHorarioIa = 'operativo';

    /** @var array<string, mixed>|null */
    public ?array $analisisHorarioIa = null;

    public function mount(): void
    {
        $this->nivel = Nivel::query()
            ->where('slug', $this->slug_nivel)
            ->firstOrFail();

        $this->esBachillerato = (int) $this->nivel->id === 4;

        $this->niveles = Nivel::query()
            ->orderBy('id')
            ->get();

        $this->generaciones = collect();
        $this->grados = collect();
        $this->grupos = collect();
        $this->horas = collect();
        $this->dias = collect();
        $this->semestres = collect();
        $this->materiasDisponibles = collect();
        $this->horariosGuardados = collect();
        $this->talleresGuardados = collect();
        $this->ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->orderByDesc('id')
            ->get();

        $this->ciclo_escolar_id = app(ContextoCicloEscolarSesion::class)->resolver($this->ciclosEscolares);

        $this->cargarGeneraciones();

        $generacionSolicitada = request()->integer('generacion');
        if ($generacionSolicitada > 0 && $this->generaciones->contains('id', $generacionSolicitada)) {
            $this->generacion_id = $generacionSolicitada;
        }

        $this->cargarGrados();

        $gradoSolicitado = request()->integer('grado');
        if ($gradoSolicitado > 0 && $this->grados->contains('id', $gradoSolicitado)) {
            $this->grado_id = $gradoSolicitado;
        }

        $this->cargarSemestres();

        $semestreSolicitado = request()->integer('semestre');
        if (
            $this->esBachillerato
            && $semestreSolicitado > 0
            && $this->semestres->contains('id', $semestreSolicitado)
        ) {
            $this->semestre_id = $semestreSolicitado;
        }

        $this->cargarGrupos();

        $grupoSolicitado = request()->integer('grupo');
        if ($grupoSolicitado > 0 && $this->grupos->contains('id', $grupoSolicitado)) {
            $this->grupo_id = $grupoSolicitado;
        }

        $this->cargarHoras();
        $this->cargarDias();
        $this->cargarMateriasDisponibles();
        $this->cargarHorariosGuardados();
        $this->cargarTalleresGuardados();
        $this->sincronizarSeleccionesHorario();
    }

    #[On('taller-conjunto-actualizado')]
    public function refrescarTalleresConjuntos(): void
    {
        $this->invalidarAnalisisHorarioIa();
        $this->resetEstadoTraslapeProfesor();
        $this->cargarHorariosGuardados();
        $this->cargarTalleresGuardados();
        $this->sincronizarSeleccionesHorario();
    }

    #[On('refrescarHorasDias')]
    public function refrescarHorasDias(): void
    {
        $this->invalidarAnalisisHorarioIa();
        $this->resetEstadoTraslapeProfesor();
        $this->mensajeActualizacionHorario = 'Actualizando horarios...';

        $this->cargarHoras();
        $this->cargarDias();
        $this->cargarHorariosGuardados();
        $this->cargarTalleresGuardados();
        $this->sincronizarSeleccionesHorario();

        $this->mensajeActualizacionHorario = 'Horario actualizado correctamente.';
    }

    public function render()
    {
        return view('livewire.accion.horario');
    }

}
