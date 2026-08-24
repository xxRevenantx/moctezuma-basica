<?php

namespace App\Livewire\Accion\Concerns;

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

trait GestionaContextoCalificaciones
{
    private function cargarContextoBusquedaGlobal(): void
    {
        if (request()->string('origen')->toString() !== 'busqueda-global') {
            return;
        }

        abort_if(
            $this->esProfesorAutenticado,
            403,
            'La búsqueda académica global está reservada para administración.'
        );

        $this->contextoBusquedaGlobal = true;
        $this->alumnoBusquedaId = request()->integer('alumno') ?: null;
        $this->periodoBusquedaGlobalId = request()->integer('periodo') ?: null;

        $periodo = $this->periodoBusquedaGlobalId
            ? Periodos::query()
                ->whereKey($this->periodoBusquedaGlobalId)
                ->where('nivel_id', $this->nivel_id)
                ->first()
            : null;

        $cicloId = request()->integer('ciclo_escolar_id')
            ?: (int) ($periodo?->ciclo_escolar_id ?? 0);

        if ($cicloId > 0) {
            $ciclo = CicloEscolar::query()->find($cicloId);

            if ($ciclo && ! $this->ciclosEscolares->contains('id', $ciclo->id)) {
                $this->ciclosEscolares->prepend($ciclo);
            }

            $this->ciclo_escolar_id = $ciclo?->id;
            $this->cargarGeneraciones();
            $this->cargarPeriodosBasicaDisponibles();
        }

        $generacionId = request()->integer('generacion') ?: (int) ($periodo?->generacion_id ?? 0);
        $gradoId = request()->integer('grado');
        $grupoId = request()->integer('grupo');
        $semestreId = request()->integer('semestre') ?: (int) ($periodo?->semestre_id ?? 0);

        $generacion = Generacion::query()
            ->whereKey($generacionId)
            ->where('nivel_id', $this->nivel_id)
            ->first();

        if (! $generacion) {
            $this->mensajeContexto = 'No fue posible restaurar la generación de la calificación seleccionada.';
            return;
        }

        if (! $this->generaciones->contains('id', $generacion->id)) {
            $this->generaciones->prepend($generacion);
        }

        $this->generacion_id = $generacion->id;
        $this->cargarGrados();

        if (! $this->grados->contains(fn ($grado) => (int) $grado->id === $gradoId)) {
            $this->mensajeContexto = 'El grado guardado en la calificación no pertenece al ciclo escolar seleccionado.';
            return;
        }

        $this->grado_id = $gradoId;

        if ($this->esBachillerato) {
            $this->cargarSemestres();

            if (! $this->semestres->contains(fn ($semestre) => (int) $semestre->id === $semestreId)) {
                $this->mensajeContexto = 'El semestre de la calificación ya no está disponible en ese ciclo.';
                return;
            }

            $this->semestre_id = $semestreId;
        }

        $this->cargarGrupos();

        if (! $this->grupos->contains(fn ($grupo) => (int) $grupo->id === $grupoId)) {
            $this->mensajeContexto = 'El grupo de la calificación no está disponible en el ciclo escolar guardado.';
            return;
        }

        $this->grupo_id = $grupoId;

        if ($this->esBachillerato) {
            $this->cargarParcialesDisponibles();
            $this->parcial_bachillerato_id = request()->integer('parcial')
                ?: $periodo?->parcial_bachillerato_id;
        } else {
            $this->periodo_basica_id = request()->integer('periodo_basica')
                ?: $periodo?->periodo_basica_id;
        }

        $buscar = trim((string) request('buscar', ''));

        if ($buscar === '' && $this->alumnoBusquedaId) {
            $buscar = (string) Inscripcion::withTrashed()
                ->whereKey($this->alumnoBusquedaId)
                ->value('matricula');
        }

        $this->busqueda = $buscar;

        if ($this->puedeCargarDatos()) {
            $this->cargarDatos();
        }
    }

    public function getEsProfesorAutenticadoProperty(): bool
    {
        return (bool) auth()->user()?->isProfessor();
    }

    public function getDeclaracionConformidadProperty(): string
    {
        return CalificacionEntregaService::DECLARATION;
    }

    public function getEntregaConfirmadaActualProperty(): ?CalificacionEntrega
    {
        if (! $this->esProfesorAutenticado || blank($this->periodo_id) || blank($this->grupo_id)) {
            return null;
        }

        return CalificacionEntrega::query()
            ->where('user_id', auth()->id())
            ->where('periodo_id', (int) $this->periodo_id)
            ->where('grupo_id', (int) $this->grupo_id)
            ->where('estado', 'confirmada')
            ->latest('confirmada_at')
            ->first();
    }

    public function getCapturaCompletaProperty(): bool
    {
        if (count($this->inscripciones) === 0 || count($this->materias) === 0) {
            return false;
        }

        foreach (collect($this->inscripciones)->pluck('inscripcion_id') as $inscripcionId) {
            foreach (collect($this->materias)->pluck('id') as $asignacionId) {
                if ($this->normalizarCalificacion($this->calificaciones[$inscripcionId][$asignacionId] ?? null) === null) {
                    return false;
                }
            }
        }

        return true;
    }

    public function getEsBachilleratoProperty(): bool
    {
        return $this->slug_nivel === 'bachillerato';
    }

    private function cicloSeleccionadoEsActual(): bool
    {
        if (blank($this->ciclo_escolar_id)) {
            return false;
        }

        $ciclo = $this->ciclosEscolares
            ->first(fn ($item) => (int) $item->id === (int) $this->ciclo_escolar_id)
            ?? CicloEscolar::query()->find($this->ciclo_escolar_id);

        return (bool) ($ciclo?->es_actual) && blank($ciclo?->cerrado_at);
    }

    public function getEsConsultaHistoricaProperty(): bool
    {
        if ($this->periodoSeleccionado) {
            return (bool) ($this->periodoSeleccionado['ciclo_cerrado'] ?? false);
        }

        return filled($this->ciclo_escolar_id) && ! $this->cicloSeleccionadoEsActual();
    }

    public function getModoConsultaProperty(): string
    {
        return $this->esConsultaHistorica ? 'historico' : 'actual';
    }

    public function getPuedeAdministrarCorreccionHistoricaProperty(): bool
    {
        return ($this->esConsultaHistorica || $this->hayAlumnosConContextoPendiente)
            && (bool) auth()->user()?->is_admin;
    }

    public function getEdicionCalificacionesHabilitadaProperty(): bool
    {
        if ($this->esProfesorAutenticado && $this->entregaConfirmadaActual) {
            return false;
        }

        if ($this->esConsultaHistorica) {
            return $this->puedeAdministrarCorreccionHistorica
                && $this->correccionHistoricaHabilitada;
        }

        return (bool) auth()->user()?->canAccess('calificaciones.capturar');
    }

    public function getMotivosCorreccionHistoricaProperty(): array
    {
        return [
            'error_captura' => 'Error de captura',
            'calificacion_pendiente' => 'Calificación pendiente',
            'revision_docente' => 'Revisión docente',
            'correccion_administrativa' => 'Corrección administrativa',
            'aclaracion_oficial' => 'Aclaración o resolución oficial',
            'otro' => 'Otro motivo',
        ];
    }

    public function getMotivoCorreccionCompletoProperty(): string
    {
        $etiqueta = $this->motivosCorreccionHistorica[$this->motivoCorreccionCatalogo] ?? 'Corrección histórica';
        $detalle = trim($this->detalleCorreccionHistorica);

        return $detalle !== '' ? $etiqueta.': '.$detalle : $etiqueta;
    }

    public function seleccionarModoConsulta(string $modo): void
    {
        if (! in_array($modo, ['actual', 'historico'], true)) {
            return;
        }

        $ciclo = $modo === 'actual'
            ? $this->ciclosEscolares->first(fn ($item) => (bool) $item->es_actual && blank($item->cerrado_at))
            : $this->ciclosEscolares->first(fn ($item) => ! (bool) $item->es_actual || filled($item->cerrado_at));

        if (! $ciclo) {
            $this->mensajeContexto = $modo === 'actual'
                ? 'No existe un ciclo escolar actual disponible para este nivel.'
                : 'No existen ciclos históricos disponibles para este nivel.';
            return;
        }

        $this->ciclo_escolar_id = (int) $ciclo->id;
        $this->updatedCicloEscolarId($this->ciclo_escolar_id);
    }

    public function abrirCorreccionHistorica(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Solo administración puede habilitar correcciones históricas.');

        if (! $this->esConsultaHistorica && ! $this->hayAlumnosConContextoPendiente) {
            $this->addError('calificaciones', 'No hay un ciclo histórico ni alumnos pendientes de confirmar por generación.');
            return;
        }

        $this->resetErrorBag(['motivoCorreccionCatalogo', 'detalleCorreccionHistorica']);
        $this->mostrarModalCorreccionHistorica = true;
    }

    public function cancelarCorreccionHistorica(): void
    {
        $this->mostrarModalCorreccionHistorica = false;
        $this->resetErrorBag(['motivoCorreccionCatalogo', 'detalleCorreccionHistorica']);
    }

    public function habilitarCorreccionHistorica(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Solo administración puede habilitar correcciones históricas.');

        if (! $this->esConsultaHistorica && ! $this->hayAlumnosConContextoPendiente) {
            $this->addError('detalleCorreccionHistorica', 'No hay un contexto histórico o inferido que requiera autorización.');
            return;
        }

        $this->validate([
            'motivoCorreccionCatalogo' => ['required', 'string', 'in:'.implode(',', array_keys($this->motivosCorreccionHistorica))],
            'detalleCorreccionHistorica' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'motivoCorreccionCatalogo.required' => 'Selecciona el motivo de la corrección.',
            'motivoCorreccionCatalogo.in' => 'El motivo seleccionado no es válido.',
            'detalleCorreccionHistorica.required' => 'Describe por qué se habilita la corrección histórica.',
            'detalleCorreccionHistorica.min' => 'La descripción debe contener al menos 10 caracteres.',
            'detalleCorreccionHistorica.max' => 'La descripción no debe superar 1000 caracteres.',
        ]);

        $this->correccionHistoricaHabilitada = true;
        $this->correccionHistoricaIniciadaEn = now()->toDateTimeString();
        $this->motivo_guardado = $this->motivoCorreccionCompleto;
        $this->mostrarModalCorreccionHistorica = false;

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => $this->esConsultaHistorica ? 'Corrección histórica habilitada' : 'Inclusión por generación habilitada',
            'text' => 'Puedes editar o importar calificaciones del contexto seleccionado. Cada cambio y asignación inferida quedará auditado.',
            'position' => 'top-end',
        ]);
    }

    public function finalizarCorreccionHistorica(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Solo administración puede finalizar correcciones históricas.');

        if ($this->hayCambios) {
            $this->addError('calificaciones', 'Guarda o descarta los cambios pendientes antes de finalizar la corrección histórica.');
            return;
        }

        $this->correccionHistoricaHabilitada = false;
        $this->correccionHistoricaIniciadaEn = null;
        $this->motivoCorreccionCatalogo = '';
        $this->detalleCorreccionHistorica = '';
        $this->motivo_guardado = '';

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Sesión de corrección finalizada',
            'text' => $this->esConsultaHistorica
                ? 'El ciclo volvió al modo de consulta protegida.'
                : 'Los contextos inferidos volvieron a quedar protegidos hasta una nueva autorización.',
            'position' => 'top-end',
        ]);
    }

    public function getHayAlumnosIncluidosPorGeneracionProperty(): bool
    {
        return collect($this->inscripciones)
            ->contains(fn (array $fila) => (bool) ($fila['incluido_por_generacion'] ?? false));
    }

    public function getCantidadAlumnosIncluidosPorGeneracionProperty(): int
    {
        return collect($this->inscripciones)
            ->filter(fn (array $fila) => (bool) ($fila['incluido_por_generacion'] ?? false))
            ->count();
    }

    public function getHayAlumnosConContextoPendienteProperty(): bool
    {
        return collect($this->inscripciones)
            ->contains(fn (array $fila) => (bool) ($fila['asignacion_contexto_pendiente'] ?? false));
    }

    public function getCantidadAlumnosConContextoPendienteProperty(): int
    {
        return collect($this->inscripciones)
            ->filter(fn (array $fila) => (bool) ($fila['asignacion_contexto_pendiente'] ?? false))
            ->count();
    }

    public function puedeEditarFilaCalificacion(int $inscripcionId): bool
    {
        if (! $this->edicionCalificacionesHabilitada) {
            return false;
        }

        $fila = collect($this->inscripciones)->firstWhere('inscripcion_id', $inscripcionId);

        if (! (bool) ($fila['asignacion_contexto_pendiente'] ?? false)) {
            return true;
        }

        return $this->correccionHistoricaHabilitada
            && (bool) auth()->user()?->is_admin;
    }

    public function etiquetaEstatusHistorico(?string $estatus): string
    {
        return match ($estatus ?: 'activo') {
            'activo' => 'Activo en el ciclo',
            'reingreso' => 'Reingreso',
            'promovido' => 'Promovido',
            'no_promovido' => 'No promovido',
            'egresado' => 'Egresado',
            'baja_temporal' => 'Baja temporal',
            'baja_definitiva' => 'Baja definitiva',
            'trasladado' => 'Trasladado',
            'suspendido' => 'Suspendido',
            'inactivo' => 'Inactivo',
            default => Str::headline((string) $estatus),
        };
    }

    public function claseEstatusHistorico(?string $estatus): string
    {
        return match ($estatus ?: 'activo') {
            'activo', 'reingreso' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300',
            'promovido' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300',
            'no_promovido' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300',
            'egresado' => 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/30 dark:text-violet-300',
            'baja_temporal', 'baja_definitiva' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300',
            'trasladado' => 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-300',
            'suspendido', 'inactivo' => 'border-slate-200 bg-slate-50 text-slate-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300',
            default => 'border-slate-200 bg-slate-50 text-slate-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300',
        };
    }

    public function cargarCatalogos(): void
    {
        $this->cargarCiclosEscolares();
        $this->seleccionarCicloInicial();
        $this->cargarGeneraciones();
        $this->cargarPeriodosBasicaDisponibles();

        $this->grados = collect();
        $this->grupos = collect();
        $this->semestres = collect();
        $this->parciales = collect();
    }

    private function cargarCiclosEscolares(): void
    {
        $this->ciclosEscolares = CicloEscolar::query()
            ->where(function ($query): void {
                $query
                    ->whereExists(function ($subquery): void {
                        $subquery->selectRaw('1')
                            ->from('periodos')
                            ->whereColumn('periodos.ciclo_escolar_id', 'ciclo_escolares.id')
                            ->where('periodos.nivel_id', $this->nivel_id);
                    })
                    ->orWhereExists(function ($subquery): void {
                        $subquery->selectRaw('1')
                            ->from('grupos')
                            ->whereColumn('grupos.ciclo_escolar_id', 'ciclo_escolares.id')
                            ->where('grupos.nivel_id', $this->nivel_id);
                    })
                    ->orWhereExists(function ($subquery): void {
                        $subquery->selectRaw('1')
                            ->from('inscripcion_ciclos')
                            ->whereColumn('inscripcion_ciclos.ciclo_escolar_id', 'ciclo_escolares.id')
                            ->where('inscripcion_ciclos.nivel_id', $this->nivel_id);
                    })
                    ->orWhereExists(function ($subquery): void {
                        $subquery->selectRaw('1')
                            ->from('calificaciones')
                            ->whereColumn('calificaciones.ciclo_escolar_id', 'ciclo_escolares.id')
                            ->where('calificaciones.nivel_id', $this->nivel_id);
                    });
            })
            ->when($this->esProfesorAutenticado, function ($query): void {
                $personaId = (int) auth()->user()->persona_id;
                $query->where('es_actual', true)
                    ->whereNull('cerrado_at')
                    ->whereExists(function ($subquery) use ($personaId): void {
                        $subquery->selectRaw('1')
                            ->from('asignacion_materias')
                            ->whereColumn('asignacion_materias.ciclo_escolar_id', 'ciclo_escolares.id')
                            ->where('asignacion_materias.profesor_id', $personaId)
                            ->where('asignacion_materias.nivel_id', $this->nivel_id)
                            ->where('asignacion_materias.estado', AsignacionMateria::ESTADO_ACTIVA);
                    });
            })
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->get();
    }

    private function seleccionarCicloInicial(): void
    {
        if (filled($this->ciclo_escolar_id)) {
            return;
        }

        $actual = $this->ciclosEscolares->firstWhere('es_actual', true)
            ?? $this->ciclosEscolares->first();

        $this->ciclo_escolar_id = $actual?->id;
    }

    private function cargarGeneraciones(): void
    {
        if (blank($this->ciclo_escolar_id)) {
            $this->generaciones = collect();
            return;
        }

        $cicloId = (int) $this->ciclo_escolar_id;

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel_id)
            ->where(function ($query) use ($cicloId): void {
                $query
                    ->whereExists(function ($subquery) use ($cicloId): void {
                        $subquery->selectRaw('1')
                            ->from('periodos')
                            ->whereColumn('periodos.generacion_id', 'generaciones.id')
                            ->where('periodos.ciclo_escolar_id', $cicloId)
                            ->where('periodos.nivel_id', $this->nivel_id);
                    })
                    ->orWhereExists(function ($subquery) use ($cicloId): void {
                        $subquery->selectRaw('1')
                            ->from('grupos')
                            ->whereColumn('grupos.generacion_id', 'generaciones.id')
                            ->where('grupos.ciclo_escolar_id', $cicloId)
                            ->where('grupos.nivel_id', $this->nivel_id);
                    })
                    ->orWhereExists(function ($subquery) use ($cicloId): void {
                        $subquery->selectRaw('1')
                            ->from('inscripcion_ciclos')
                            ->whereColumn('inscripcion_ciclos.generacion_id', 'generaciones.id')
                            ->where('inscripcion_ciclos.ciclo_escolar_id', $cicloId)
                            ->where('inscripcion_ciclos.nivel_id', $this->nivel_id);
                    })
                    ->orWhereExists(function ($subquery) use ($cicloId): void {
                        $subquery->selectRaw('1')
                            ->from('calificaciones')
                            ->whereColumn('calificaciones.generacion_id', 'generaciones.id')
                            ->where('calificaciones.ciclo_escolar_id', $cicloId)
                            ->where('calificaciones.nivel_id', $this->nivel_id);
                    });
            })
            ->when($this->esProfesorAutenticado, function ($query) use ($cicloId): void {
                $personaId = (int) auth()->user()->persona_id;
                $query->whereExists(function ($subquery) use ($cicloId, $personaId): void {
                    $subquery->selectRaw('1')
                        ->from('asignacion_materias')
                        ->whereColumn('asignacion_materias.generacion_id', 'generaciones.id')
                        ->where('asignacion_materias.ciclo_escolar_id', $cicloId)
                        ->where('asignacion_materias.nivel_id', $this->nivel_id)
                        ->where('asignacion_materias.profesor_id', $personaId)
                        ->where('asignacion_materias.estado', AsignacionMateria::ESTADO_ACTIVA);
                });
            })
            ->orderByDesc('anio_ingreso')
            ->orderByDesc('id')
            ->get();
    }

    private function cargarPeriodosBasicaDisponibles(): void
    {
        if ($this->esBachillerato || blank($this->ciclo_escolar_id)) {
            $this->periodosBasica = collect();
            return;
        }

        $this->periodosBasica = PeriodosBasica::query()
            ->whereHas('periodos', function ($query): void {
                $query->where('nivel_id', $this->nivel_id)
                    ->where('ciclo_escolar_id', $this->ciclo_escolar_id);
            })
            ->orderBy('periodo')
            ->get();
    }

    public function updatedCicloEscolarId($value = null): void
    {
        $this->resetEstadoAcademico([
            'generacion_id',
            'grado_id',
            'grupo_id',
            'semestre_id',
            'parcial_bachillerato_id',
            'periodo_basica_id',
            'boleta_inscripcion_id',
            'reconocimiento_inscripcion_id',
        ]);

        $this->generaciones = collect();
        $this->grados = collect();
        $this->grupos = collect();
        $this->semestres = collect();
        $this->parciales = collect();
        $this->periodosBasica = collect();

        if (blank($value)) {
            return;
        }

        if (! $this->ciclosEscolares->contains(fn ($ciclo) => (int) $ciclo->id === (int) $value)) {
            $this->ciclo_escolar_id = null;
            $this->mensajeContexto = 'El ciclo escolar seleccionado no tiene información académica para este nivel.';
            return;
        }

        $this->cargarGeneraciones();
        $this->cargarPeriodosBasicaDisponibles();

        if ($this->generaciones->isEmpty()) {
            $this->mensajeContexto = 'El ciclo escolar seleccionado no tiene generaciones con grupos, alumnos o calificaciones para este nivel.';
        }
    }

    public function updatedGeneracionId($value = null): void
    {
        $this->resetEstadoAcademico([
            'grado_id',
            'grupo_id',
            'semestre_id',
            'parcial_bachillerato_id',
            'periodo_basica_id',
            'boleta_inscripcion_id',
            'reconocimiento_inscripcion_id',
        ]);

        $this->grados = collect();
        $this->grupos = collect();
        $this->semestres = collect();
        $this->parciales = collect();

        if (blank($value)) {
            return;
        }

        $esValida = $this->generaciones
            ->contains(fn ($generacion) => (int) $generacion->id === (int) $value);

        if (! $esValida) {
            $this->generacion_id = null;
            $this->mensajeContexto = 'La generación seleccionada no pertenece al nivel y ciclo escolar actuales.';
            return;
        }

        $this->cargarGrados();
    }

    public function updatedGradoId($value = null): void
    {
        $this->resetEstadoAcademico([
            'grupo_id',
            'semestre_id',
            'parcial_bachillerato_id',
            'periodo_basica_id',
            'boleta_inscripcion_id',
            'reconocimiento_inscripcion_id',
        ]);

        $this->grupos = collect();
        $this->semestres = collect();
        $this->parciales = collect();

        if (blank($value)) {
            return;
        }

        $esValido = $this->grados->contains(fn($grado) => (int) $grado->id === (int) $value);

        if (!$esValido) {
            $this->grado_id = null;
            $this->mensajeContexto = 'El grado seleccionado no corresponde a la generación actual.';
            return;
        }

        if ($this->esBachillerato) {
            $this->cargarSemestres();
            return;
        }

        $this->cargarGrupos();
    }

    public function updatedSemestreId($value = null): void
    {
        $this->resetEstadoAcademico([
            'grupo_id',
            'parcial_bachillerato_id',
            'boleta_inscripcion_id',
            'reconocimiento_inscripcion_id',
        ]);

        $this->grupos = collect();
        $this->parciales = collect();

        if (blank($value)) {
            return;
        }

        $esValido = $this->semestres->contains(fn($semestre) => (int) $semestre->id === (int) $value);

        if (!$esValido) {
            $this->semestre_id = null;
            $this->mensajeContexto = 'Ese semestre no tiene periodos registrados para la generación seleccionada.';
            return;
        }

        $this->cargarGrupos();
        $this->cargarParcialesDisponibles();
    }

    public function updatedGrupoId($value = null): void
    {
        $this->resetEstadoAcademico([
            'parcial_bachillerato_id',
            'periodo_basica_id',
            'boleta_inscripcion_id',
            'reconocimiento_inscripcion_id',
        ]);

        if (blank($value)) {
            return;
        }

        $esValido = $this->grupos->contains(fn($grupo) => (int) $grupo->id === (int) $value);

        if (!$esValido) {
            $this->grupo_id = null;
            $this->mensajeContexto = 'El grupo seleccionado no corresponde al contexto académico actual.';
            return;
        }

        if (!$this->esBachillerato) {
            return;
        }

        /*
         * En bachillerato no se cargan datos al seleccionar grupo.
         * Primero se debe seleccionar un parcial disponible.
         */
    }

    public function updatedParcialBachilleratoId($value = null): void
    {
        $this->resetEstadoAcademico([
            'boleta_inscripcion_id',
            'reconocimiento_inscripcion_id',
        ]);

        if (blank($value)) {
            return;
        }

        $esValido = $this->parciales->contains(fn($parcial) => (int) $parcial->id === (int) $value);

        if (!$esValido) {
            $this->parcial_bachillerato_id = null;
            $this->mensajeContexto = 'El parcial seleccionado no tiene un periodo registrado para este semestre.';
            return;
        }

        $this->cargarDatos();
    }

    public function updatedPeriodoBasicaId($value = null): void
    {
        $this->resetEstadoAcademico([
            'boleta_inscripcion_id',
            'reconocimiento_inscripcion_id',
        ]);

        if (blank($value)) {
            return;
        }

        $this->cargarDatos();
    }

    public function updatedBusqueda(): void
    {
        $this->aplicarFiltroEstado();
    }

    public function updatedFiltroEstado(): void
    {
        $this->aplicarFiltroEstado();
    }

    public function updatedFiltroRegistros(): void
    {
        $this->aplicarFiltroEstado();
    }

    public function updatedFiltroEstatusHistorico(): void
    {
        $this->aplicarFiltroEstado();
    }

    public function updatedOrdenPromedio(): void
    {
        $this->aplicarFiltroEstado();
    }

    public function updatedCalificaciones($value = null, $key = null): void
    {
        $this->limpiarDiagnosticoIa();

        /*
         * Solo se recalculan promedios cuando Livewire recibe el cambio.
         * Con wire:model.blur ya no se ejecuta en cada tecla.
         */
        $this->calcularPromedios();

        /*
         * Solo se reaplica el filtro si realmente hay un filtro activo.
         * Esto evita recorrer toda la tabla innecesariamente.
         */
        if (
            $this->filtro_estado !== ''
            || $this->filtro_registros !== 'todos'
            || $this->filtro_estatus_historico !== ''
            || $this->orden_promedio !== ''
        ) {
            $this->aplicarFiltroEstado();
        }
    }

    private function resetEstadoAcademico(array $camposExtra = []): void
    {
        $campos = array_merge($camposExtra, [
            'periodo_id',
            'periodoSeleccionado',
            'filtro_estado',
            'filtro_registros',
            'filtro_estatus_historico',
            'orden_promedio',
            'inscripciones',
            'inscripcionesTabla',
            'materias',
            'calificaciones',
            'calificacionesOriginales',
            'observaciones',
            'observacionesOriginales',
            'promedios',
            'mostrarModalBitacora',
            'mostrarModalRevision',
            'resumenRevision',
            'motivo_guardado',
            'acepta_conformidad',
            'password_confirmacion',
            'correccionHistoricaHabilitada',
            'mostrarModalCorreccionHistorica',
            'motivoCorreccionCatalogo',
            'detalleCorreccionHistorica',
            'correccionHistoricaIniciadaEn',
            'archivo_calificaciones',
            'resumenImportacion',
            'diagnosticoIa',
            'diagnosticoIaGeneradoEn',
            'mensajeContexto',
        ]);

        $this->reset($campos);
    }

    private function puedeCargarDatos(): bool
    {
        if ($this->esBachillerato) {
            return filled($this->ciclo_escolar_id)
                && filled($this->generacion_id)
                && filled($this->grado_id)
                && filled($this->semestre_id)
                && filled($this->grupo_id)
                && filled($this->parcial_bachillerato_id);
        }

        return filled($this->ciclo_escolar_id)
            && filled($this->generacion_id)
            && filled($this->grado_id)
            && filled($this->grupo_id)
            && filled($this->periodo_basica_id);
    }

    private function cargarGrados(): void
    {
        if (blank($this->ciclo_escolar_id) || blank($this->generacion_id)) {
            $this->grados = collect();
            return;
        }

        $cicloId = (int) $this->ciclo_escolar_id;
        $generacionId = (int) $this->generacion_id;

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->where(function ($query) use ($cicloId, $generacionId): void {
                $query
                    ->whereExists(function ($subquery) use ($cicloId, $generacionId): void {
                        $subquery->selectRaw('1')
                            ->from('grupos')
                            ->whereColumn('grupos.grado_id', 'grados.id')
                            ->where('grupos.ciclo_escolar_id', $cicloId)
                            ->where('grupos.nivel_id', $this->nivel_id)
                            ->where('grupos.generacion_id', $generacionId);
                    })
                    ->orWhereExists(function ($subquery) use ($cicloId, $generacionId): void {
                        $subquery->selectRaw('1')
                            ->from('inscripcion_ciclos')
                            ->whereColumn('inscripcion_ciclos.grado_id', 'grados.id')
                            ->where('inscripcion_ciclos.ciclo_escolar_id', $cicloId)
                            ->where('inscripcion_ciclos.nivel_id', $this->nivel_id)
                            ->where('inscripcion_ciclos.generacion_id', $generacionId);
                    })
                    ->orWhereExists(function ($subquery) use ($cicloId, $generacionId): void {
                        $subquery->selectRaw('1')
                            ->from('calificaciones')
                            ->whereColumn('calificaciones.grado_id', 'grados.id')
                            ->where('calificaciones.ciclo_escolar_id', $cicloId)
                            ->where('calificaciones.nivel_id', $this->nivel_id)
                            ->where('calificaciones.generacion_id', $generacionId);
                    });
            })
            ->when($this->esProfesorAutenticado, function ($query) use ($cicloId, $generacionId): void {
                $personaId = (int) auth()->user()->persona_id;
                $query->whereExists(function ($subquery) use ($cicloId, $generacionId, $personaId): void {
                    $subquery->selectRaw('1')
                        ->from('asignacion_materias')
                        ->whereColumn('asignacion_materias.grado_id', 'grados.id')
                        ->where('asignacion_materias.ciclo_escolar_id', $cicloId)
                        ->where('asignacion_materias.nivel_id', $this->nivel_id)
                        ->where('asignacion_materias.generacion_id', $generacionId)
                        ->where('asignacion_materias.profesor_id', $personaId)
                        ->where('asignacion_materias.estado', AsignacionMateria::ESTADO_ACTIVA);
                });
            })
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        if ($this->grados->isEmpty()) {
            $this->mensajeContexto = 'La generación seleccionada no tiene grados con grupos, alumnos o calificaciones en este ciclo escolar.';
        }
    }

    private function cargarSemestres(): void
    {
        if (
            ! $this->esBachillerato
            || blank($this->ciclo_escolar_id)
            || blank($this->generacion_id)
            || blank($this->grado_id)
        ) {
            $this->semestres = collect();
            return;
        }

        $cicloId = (int) $this->ciclo_escolar_id;
        $generacionId = (int) $this->generacion_id;
        $gradoId = (int) $this->grado_id;

        $this->semestres = Semestre::query()
            ->where('grado_id', $gradoId)
            ->where(function ($query) use ($cicloId, $generacionId, $gradoId): void {
                $query
                    ->whereExists(function ($subquery) use ($cicloId, $generacionId, $gradoId): void {
                        $subquery->selectRaw('1')
                            ->from('periodos')
                            ->whereColumn('periodos.semestre_id', 'semestres.id')
                            ->where('periodos.ciclo_escolar_id', $cicloId)
                            ->where('periodos.nivel_id', $this->nivel_id)
                            ->where('periodos.generacion_id', $generacionId);
                    })
                    ->orWhereExists(function ($subquery) use ($cicloId, $generacionId, $gradoId): void {
                        $subquery->selectRaw('1')
                            ->from('grupos')
                            ->whereColumn('grupos.semestre_id', 'semestres.id')
                            ->where('grupos.ciclo_escolar_id', $cicloId)
                            ->where('grupos.nivel_id', $this->nivel_id)
                            ->where('grupos.generacion_id', $generacionId)
                            ->where('grupos.grado_id', $gradoId);
                    })
                    ->orWhereExists(function ($subquery) use ($cicloId, $generacionId, $gradoId): void {
                        $subquery->selectRaw('1')
                            ->from('inscripcion_ciclos')
                            ->whereColumn('inscripcion_ciclos.semestre_id', 'semestres.id')
                            ->where('inscripcion_ciclos.ciclo_escolar_id', $cicloId)
                            ->where('inscripcion_ciclos.nivel_id', $this->nivel_id)
                            ->where('inscripcion_ciclos.generacion_id', $generacionId)
                            ->where('inscripcion_ciclos.grado_id', $gradoId);
                    })
                    ->orWhereExists(function ($subquery) use ($cicloId, $generacionId, $gradoId): void {
                        $subquery->selectRaw('1')
                            ->from('calificaciones')
                            ->whereColumn('calificaciones.semestre_id', 'semestres.id')
                            ->where('calificaciones.ciclo_escolar_id', $cicloId)
                            ->where('calificaciones.nivel_id', $this->nivel_id)
                            ->where('calificaciones.generacion_id', $generacionId)
                            ->where('calificaciones.grado_id', $gradoId);
                    });
            })
            ->when($this->esProfesorAutenticado, function ($query) use ($cicloId, $generacionId, $gradoId): void {
                $personaId = (int) auth()->user()->persona_id;
                $query->whereExists(function ($subquery) use ($cicloId, $generacionId, $gradoId, $personaId): void {
                    $subquery->selectRaw('1')
                        ->from('asignacion_materias')
                        ->whereColumn('asignacion_materias.semestre_id', 'semestres.id')
                        ->where('asignacion_materias.ciclo_escolar_id', $cicloId)
                        ->where('asignacion_materias.nivel_id', $this->nivel_id)
                        ->where('asignacion_materias.generacion_id', $generacionId)
                        ->where('asignacion_materias.grado_id', $gradoId)
                        ->where('asignacion_materias.profesor_id', $personaId)
                        ->where('asignacion_materias.estado', AsignacionMateria::ESTADO_ACTIVA);
                });
            })
            ->orderBy('numero')
            ->get();

        if ($this->semestres->isEmpty()) {
            $this->mensajeContexto = 'No existen semestres con periodos, grupos o historial para esta generación dentro del ciclo seleccionado.';
        }
    }

    private function cargarParcialesDisponibles(): void
    {
        $this->parciales = collect();

        if (
            ! $this->esBachillerato
            || blank($this->ciclo_escolar_id)
            || blank($this->generacion_id)
            || blank($this->semestre_id)
        ) {
            return;
        }

        $this->parciales = Parcial::query()
            ->whereHas('periodos', function ($query): void {
                $query->where('nivel_id', $this->nivel_id)
                    ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                    ->where('generacion_id', $this->generacion_id)
                    ->where('semestre_id', $this->semestre_id);
            })
            ->orderBy('parcial')
            ->get();

        if ($this->parciales->isEmpty()) {
            $this->mensajeContexto = 'El semestre seleccionado no tiene parciales registrados en este ciclo escolar.';
        }
    }

    private function cargarGrupos(): void
    {
        $this->grupos = collect();

        if (
            blank($this->ciclo_escolar_id)
            || blank($this->generacion_id)
            || blank($this->grado_id)
        ) {
            return;
        }

        if ($this->esBachillerato && blank($this->semestre_id)) {
            return;
        }

        $cicloId = (int) $this->ciclo_escolar_id;

        $this->grupos = Grupo::withTrashed()
            ->with('asignacionGrupo:id,nombre')
            ->leftJoin('asignacion_grupos', 'asignacion_grupos.id', '=', 'grupos.asignacion_grupo_id')
            ->select('grupos.*')
            ->where('grupos.nivel_id', $this->nivel_id)
            ->where('grupos.generacion_id', $this->generacion_id)
            ->where('grupos.grado_id', $this->grado_id)
            ->when(
                $this->esBachillerato,
                fn ($query) => $query->where('grupos.semestre_id', $this->semestre_id),
                fn ($query) => $query->whereNull('grupos.semestre_id')
            )
            ->where(function ($query) use ($cicloId): void {
                $query
                    ->where('grupos.ciclo_escolar_id', $cicloId)
                    ->orWhereExists(function ($subquery) use ($cicloId): void {
                        $subquery->selectRaw('1')
                            ->from('inscripcion_ciclos')
                            ->whereColumn('inscripcion_ciclos.grupo_id', 'grupos.id')
                            ->where('inscripcion_ciclos.ciclo_escolar_id', $cicloId);
                    })
                    ->orWhereExists(function ($subquery) use ($cicloId): void {
                        $subquery->selectRaw('1')
                            ->from('inscripcion_ciclo_asignaciones')
                            ->join(
                                'inscripcion_ciclos',
                                'inscripcion_ciclos.id',
                                '=',
                                'inscripcion_ciclo_asignaciones.inscripcion_ciclo_id'
                            )
                            ->whereColumn('inscripcion_ciclo_asignaciones.grupo_id', 'grupos.id')
                            ->where('inscripcion_ciclos.ciclo_escolar_id', $cicloId);
                    })
                    ->orWhereExists(function ($subquery) use ($cicloId): void {
                        $subquery->selectRaw('1')
                            ->from('calificaciones')
                            ->whereColumn('calificaciones.grupo_id', 'grupos.id')
                            ->where('calificaciones.ciclo_escolar_id', $cicloId);
                    });
            })
            ->when($this->esProfesorAutenticado, function ($query) use ($cicloId): void {
                $personaId = (int) auth()->user()->persona_id;
                $query->whereExists(function ($subquery) use ($cicloId, $personaId): void {
                    $subquery->selectRaw('1')
                        ->from('asignacion_materias')
                        ->whereColumn('asignacion_materias.grupo_id', 'grupos.id')
                        ->where('asignacion_materias.ciclo_escolar_id', $cicloId)
                        ->where('asignacion_materias.nivel_id', $this->nivel_id)
                        ->where('asignacion_materias.generacion_id', $this->generacion_id)
                        ->where('asignacion_materias.grado_id', $this->grado_id)
                        ->when(
                            $this->esBachillerato,
                            fn ($inner) => $inner->where('asignacion_materias.semestre_id', $this->semestre_id),
                            fn ($inner) => $inner->whereNull('asignacion_materias.semestre_id')
                        )
                        ->where('asignacion_materias.profesor_id', $personaId)
                        ->where('asignacion_materias.estado', AsignacionMateria::ESTADO_ACTIVA);
                });
            })
            ->orderBy('asignacion_grupos.nombre')
            ->orderBy('grupos.id')
            ->get();

        if ($this->grupos->isEmpty()) {
            $this->mensajeContexto = 'No existen grupos configurados o históricos para el ciclo, generación, grado y semestre seleccionados.';
        }
    }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'generacion_id',
            'grado_id',
            'grupo_id',
            'semestre_id',
            'parcial_bachillerato_id',
            'periodo_basica_id',
            'periodo_id',
            'ciclo_escolar_id',
            'periodoSeleccionado',
            'busqueda',
            'filtro_estado',
            'filtro_registros',
            'filtro_estatus_historico',
            'orden_promedio',
            'inscripciones',
            'inscripcionesTabla',
            'materias',
            'calificaciones',
            'calificacionesOriginales',
            'observaciones',
            'observacionesOriginales',
            'promedios',
            'mostrarModalBitacora',
            'mostrarModalRevision',
            'resumenRevision',
            'motivo_guardado',
            'acepta_conformidad',
            'password_confirmacion',
            'correccionHistoricaHabilitada',
            'mostrarModalCorreccionHistorica',
            'motivoCorreccionCatalogo',
            'detalleCorreccionHistorica',
            'correccionHistoricaIniciadaEn',
            'archivo_calificaciones',
            'resumenImportacion',
            'diagnosticoIa',
            'diagnosticoIaGeneradoEn',
            'boleta_inscripcion_id',
            'reconocimiento_inscripcion_id',
            'mensajeContexto',
        ]);

        $this->generaciones = collect();
        $this->grados = collect();
        $this->grupos = collect();
        $this->semestres = collect();
        $this->parciales = collect();
        $this->periodosBasica = collect();

        $this->seleccionarCicloInicial();
        $this->cargarGeneraciones();
        $this->cargarPeriodosBasicaDisponibles();
    }

    public function cargarDatos(): void
    {
        $this->reset([
            'periodo_id',
            'periodoSeleccionado',
            'inscripciones',
            'inscripcionesTabla',
            'materias',
            'calificaciones',
            'calificacionesOriginales',
            'observaciones',
            'observacionesOriginales',
            'promedios',
            'mostrarModalBitacora',
            'mostrarModalRevision',
            'resumenRevision',
            'motivo_guardado',
            'acepta_conformidad',
            'password_confirmacion',
            'archivo_calificaciones',
            'resumenImportacion',
            'diagnosticoIa',
            'diagnosticoIaGeneradoEn',
            'mensajeContexto',
        ]);

        if (!$this->puedeCargarDatos()) {
            $this->mensajeContexto = $this->esBachillerato
                ? 'Selecciona una generación, grado, semestre, grupo y parcial válidos.'
                : 'Selecciona una generación, grado, grupo y periodo válidos.';
            return;
        }

        $this->cargarPeriodoSeleccionado();

        if (blank($this->periodo_id)) {
            $this->mensajeContexto = 'No existe un periodo registrado para la generación, semestre y parcial seleccionados.';
            return;
        }

        $this->asegurarContextoDocente();
        $this->cargarInscripciones();
        $this->cargarMaterias();

        if (empty($this->inscripciones)) {
            $this->mensajeContexto = 'El contexto seleccionado no tiene alumnos pertenecientes a ese ciclo y grupo.';
        } elseif (empty($this->materias)) {
            $this->mensajeContexto = 'El grupo seleccionado no tiene materias calificables asignadas para el ciclo escolar del periodo.';
        }

        $this->cargarCalificaciones();
        $this->calcularPromedios();
        $this->aplicarFiltroEstado();
    }

    private function asegurarContextoDocente(): void
    {
        if (! $this->esProfesorAutenticado) {
            return;
        }

        app(TeacherAcademicScopeService::class)->assertGradeContext(
            user: auth()->user(),
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            nivelId: (int) $this->nivel_id,
            generacionId: (int) $this->generacion_id,
            gradoId: (int) $this->grado_id,
            grupoId: (int) $this->grupo_id,
            semestreId: $this->esBachillerato ? (int) $this->semestre_id : null,
            periodoId: (int) $this->periodo_id,
        );
    }

}
