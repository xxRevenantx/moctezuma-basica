<?php

namespace App\Livewire\Accion;

use App\Exports\CalificacionExport;
use App\Exports\PlantillaCalificacionesImportExport;
use App\Imports\CalificacionesImport;
use App\Models\AsignacionMateria;
use App\Models\BitacoraCalificacion;
use App\Models\Calificacion as ModelsCalificacion;
use App\Models\CalificacionEntrega;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use App\Models\MateriaPromediar;
use App\Models\Nivel;
use App\Models\Parcial;
use App\Models\Periodos;
use App\Models\PeriodosBasica;
use App\Models\Semestre;
use App\Services\GroqCalificacionService;
use App\Services\CalificacionCorreccionService;
use App\Services\CicloNivelGateService;
use App\Services\HistorialCalificacionesGeneracionService;
use App\Services\ListaAcademicaService;
use App\Services\TeacherAcademicScopeService;
use App\Services\CalificacionEntregaService;
use App\Support\CalificacionBachillerato;
use App\Support\PromedioExcel;
use App\Support\ReglasMateriaBachillerato;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use Livewire\Attributes\Locked;
use Throwable;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

use App\Livewire\Accion\Concerns\GestionaContextoCalificaciones;
use App\Livewire\Accion\Concerns\GestionaCapturaCalificaciones;
use App\Livewire\Accion\Concerns\ProveeAnaliticaCalificaciones;
use App\Livewire\Accion\Concerns\GestionaIntercambioCalificaciones;

class Calificacion extends Component
{
    use WithFileUploads;

    use GestionaContextoCalificaciones;
    use GestionaCapturaCalificaciones;
    use ProveeAnaliticaCalificaciones;
    use GestionaIntercambioCalificaciones;

    private array $contextosGeneracionConfirmados = [];

    #[Locked]
    public string $slug_nivel = '';

    #[Locked]
    public $nivel_id = null;
    public $generacion_id = null;
    public $grado_id = null;
    public $grupo_id = null;
    public $semestre_id = null;

    public $parcial_bachillerato_id = null;
    public $periodo_basica_id = null;

    public $periodo_id = null;
    public $ciclo_escolar_id = null;

    public string $busqueda = '';
    public string $filtro_estado = '';
    public string $filtro_registros = 'todos';
    public string $filtro_estatus_historico = '';
    public string $orden_promedio = '';
    public string $mensajeContexto = '';

    public bool $contextoBusquedaGlobal = false;
    public ?int $alumnoBusquedaId = null;
    public ?int $periodoBusquedaGlobalId = null;

    public array $inscripciones = [];
    public array $inscripcionesTabla = [];
    public array $materias = [];
    public array $calificaciones = [];
    public array $calificacionesOriginales = [];
    public array $observaciones = [];
    public array $observacionesOriginales = [];
    public array $promedios = [];
    public array $promediosPrecisos = [];

    public bool $mostrarModalBitacora = false;
    public bool $mostrarModalRevision = false;

    public array $resumenRevision = [];
    public string $motivo_guardado = '';
    public bool $acepta_conformidad = false;
    public string $password_confirmacion = '';

    public bool $correccionHistoricaHabilitada = false;
    public bool $mostrarModalCorreccionHistorica = false;
    public string $motivoCorreccionCatalogo = '';
    public string $detalleCorreccionHistorica = '';
    public ?string $correccionHistoricaIniciadaEn = null;

    public $archivo_calificaciones = null;
    public array $resumenImportacion = [];

    public string $tipoDiagnosticoIa = 'pedagogico';
    public array $diagnosticoIa = [];
    public ?string $diagnosticoIaGeneradoEn = null;

    public $boleta_inscripcion_id = '';
    public $reconocimiento_inscripcion_id = '';

    public Collection $niveles;
    public Collection $ciclosEscolares;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $grupos;
    public Collection $semestres;
    public Collection $parciales;
    public Collection $periodosBasica;

    public ?array $periodoSeleccionado = null;

    public function boot(): void
    {
        $user = auth()->user();
        abort_unless($user?->canAccess('calificaciones.consultar'), 403);

        if ($user->isProfessor()) {
            app(TeacherAcademicScopeService::class)->personaIdOrFail($user);
        }
    }

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;

        $nivel = Nivel::query()
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $this->nivel_id = $nivel->id;

        if ($this->esProfesorAutenticado) {
            abort_if($nivel->slug === 'preescolar', 404);
            $scope = app(TeacherAcademicScopeService::class);
            abort_unless(
                $scope->assignedLevels(auth()->user())->contains(fn ($item): bool => (int) $item->id === (int) $nivel->id),
                403,
                'No tienes materias asignadas en este nivel.'
            );
        }

        $this->niveles = Nivel::query()
            ->orderBy('id')
            ->get();

        $this->ciclosEscolares = collect();
        $this->generaciones = collect();
        $this->grados = collect();
        $this->grupos = collect();
        $this->semestres = collect();
        $this->parciales = collect();
        $this->periodosBasica = collect();

        $this->cargarCatalogos();
        $this->cargarContextoBusquedaGlobal();
    }

    public function render()
    {
        return view('livewire.accion.calificacion', [
            'hayCambios' => $this->hayCambios,
            'graficasCalificaciones' => $this->graficasCalificaciones,
        ]);
    }

}
