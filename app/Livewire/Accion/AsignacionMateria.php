<?php

namespace App\Livewire\Accion;

use App\Models\AsignacionMateria as AsignacionMateriaModel;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Materia;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\PersonaNivel;
use App\Models\PersonaNivelDetalle;
use App\Models\ReasignacionDocenteLote;
use App\Models\Semestre;
use App\Models\Inscripcion;
use App\Services\CicloNivelGateService;
use App\Services\PlantillaDocenteService;
use App\Services\ReasignacionDocenteMasivaService;
use App\Services\ContextoCicloEscolarSesion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

use App\Livewire\Accion\Concerns\ConsultaCargaAcademica;
use App\Livewire\Accion\Concerns\GestionaReasignacionDocente;
use App\Livewire\Accion\Concerns\GestionaAsignacionesAcademicas;
use App\Livewire\Accion\Concerns\GestionaCopiaCargaAcademica;

class AsignacionMateria extends Component
{
    use WithPagination;
    use ConsultaCargaAcademica;
    use GestionaReasignacionDocente;
    use GestionaAsignacionesAcademicas;
    use GestionaCopiaCargaAcademica;

    public string $slug_nivel = '';
    public $nivel = null;

    public ?int $ciclo_escolar_id = null;
    public ?int $ciclo_origen_id = null;
    public bool $copiar_profesores = true;
    public bool $copiar_horarios = false;

    public string $buscar = '';
    public string $filtro_generacion = '';
    public string $filtro_estado = '';
    public string $filtro_grado = '';
    public string $filtro_semestre = '';
    public string $filtro_grupo = '';
    public string $filtro_horario = '';
    public string $filtro_profesor = '';
    public int $porPaginaMaterias = 10;
    public ?int $editandoId = null;
    public bool $modalEditarAbierto = false;
    public bool $edicionTieneHistorial = false;
    public $editar_grupo_id = '';
    public $editar_materia_id = '';
    public $editar_profesor_id = '';

    /** Selección y reasignación masiva de docentes. */
    public array $seleccionados = [];
    public bool $modalReasignacionAbierto = false;
    public bool $modalHistorialReasignacionesAbierto = false;
    public string $reasignacion_paso = 'seleccion';
    public string $reasignacion_modo = 'seleccion';
    public string $reasignacion_origen = '';
    public string $reasignacion_destino_id = '';
    public array $reasignacion_ids_base = [];
    public array $reasignacion_seleccionados = [];
    public string $reasignacion_buscar = '';
    public string $reasignacion_generacion = '';
    public string $reasignacion_estado = '';
    public string $reasignacion_grado = '';
    public string $reasignacion_semestre = '';
    public string $reasignacion_grupo = '';
    public string $reasignacion_horario = '';
    public bool $reasignacion_incluir_cerradas = false;
    public bool $reasignacion_incluir_archivadas = false;
    public bool $reasignacion_autorizar_conflictos = false;
    public string $reasignacion_motivo_conflictos = '';
    public array $reasignacion_preview = [];

    public $grupo_id = '';
    public $materia_id = '';
    public $profesor_id = '';
    public ?int $ultimoRegistroId = null;
    public string $ultimoMovimiento = '';

    public function mount($slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();

        $ciclos = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->orderByDesc('id')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual', 'cerrado_at']);

        $this->ciclo_escolar_id = app(ContextoCicloEscolarSesion::class)->resolver($ciclos);
        $this->sincronizarCicloOrigen();
    }

    private function autorizarAdministracion(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Solo administración puede confirmar, cerrar, archivar, eliminar o copiar cargas.');
    }

    public function render()
    {
        return view('livewire.accion.asignacion-materia');
    }

}
