<?php

namespace App\Livewire\Accion\Generales;

use App\Models\CambioAcademico;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\ProcesoCierreCiclo;
use App\Models\Semestre;
use App\Services\AsignacionEscolarService;
use App\Services\CierreGeneracionContinuidadService;
use App\Services\GestionAcademicaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CierreNivelContinuidad extends Component
{
    public string $slug_nivel = '';
    public ?Nivel $nivel = null;

    public Collection $ciclos;
    public Collection $generaciones;
    public Collection $gruposOrigen;
    public Collection $nivelesDestino;
    public Collection $gradosDestino;
    public Collection $semestresDestino;
    public Collection $generacionesDestino;

    public ?int $ciclo_origen_id = null;
    public ?int $generacion_id = null;
    public ?int $grupo_origen_id = null;

    public ?int $ciclo_destino_id = null;
    public ?int $nivel_destino_id = null;
    public ?int $grado_destino_id = null;
    public ?int $semestre_destino_id = null;
    public ?int $generacion_destino_id = null;
    public string $generacion_esperada = '';

    public int $paso = 1;
    public array $alumnos = [];
    public array $decisiones = [];
    public array $seleccionados = [];
    public array $grupos_continuidad = [];
    public array $grupos_repeticion = [];
    public array $vista_previa = [];

    public string $buscar = '';
    public string $filtro_resultado = '';
    public string $filtro_estatus = '';
    public string $resultado_masivo = 'continuidad_interna';
    public ?int $grupo_masivo_id = null;

    public string $fecha_efectiva = '';
    public string $motivo = '';
    public bool $cerrar_generacion = true;
    public string $confirmacion = '';
    public string $password_confirmacion = '';

    public bool $modalReversion = false;
    public ?int $procesoReversionId = null;
    public string $motivo_reversion = '';

    public bool $modalReactivar = false;
    public ?int $generacionReactivarId = null;
    public string $motivo_reactivacion = '';
    public bool $reactivar_egresados = false;
    public ?int $procesoExpandidoId = null;

    public bool $modalArchivar = false;
    public ?int $generacionArchivarId = null;
    public string $motivo_archivo = '';
    public string $confirmacion_archivo = '';
    public string $password_archivo = '';

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin || auth()->user()?->canAccess('alumnos.editar'), 403);

        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();
        $this->ciclos = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->get();
        $this->nivelesDestino = Nivel::query()->orderBy('id')->get();
        $this->gradosDestino = collect();
        $this->semestresDestino = collect();
        $this->generacionesDestino = collect();
        $this->generaciones = collect();
        $this->gruposOrigen = collect();
        $this->fecha_efectiva = now()->toDateString();
        $this->ciclo_origen_id = $this->ciclos->firstWhere('es_actual', true)?->id
            ?? $this->ciclos->first()?->id;

        $this->cargarGeneracionesOrigen();
        $this->refrescarProcesos();
    }

    public function updatedCicloOrigenId(): void
    {
        $this->ciclo_origen_id = filled($this->ciclo_origen_id) ? (int) $this->ciclo_origen_id : null;
        $this->generacion_id = null;
        $this->grupo_origen_id = null;
        $this->cargarGeneracionesOrigen();
        $this->gruposOrigen = collect();
        $this->reiniciarAsistente();
    }

    public function updatedGeneracionId(): void
    {
        $this->generacion_id = filled($this->generacion_id) ? (int) $this->generacion_id : null;
        $this->grupo_origen_id = null;
        $this->cargarGruposOrigen();
        $this->reiniciarAsistente();
    }

    public function updatedGrupoOrigenId(): void
    {
        $this->grupo_origen_id = filled($this->grupo_origen_id) ? (int) $this->grupo_origen_id : null;
        $this->cerrar_generacion = $this->grupo_origen_id === null;
        $this->reiniciarAsistente();
    }

    public function updatedCicloDestinoId(): void
    {
        $this->ciclo_destino_id = filled($this->ciclo_destino_id) ? (int) $this->ciclo_destino_id : null;
        $this->resolverGeneracionDestino();
        $this->cargarGruposDestino();
    }

    public function updatedNivelDestinoId(): void
    {
        $this->nivel_destino_id = filled($this->nivel_destino_id) ? (int) $this->nivel_destino_id : null;
        $this->grado_destino_id = null;
        $this->semestre_destino_id = null;
        $this->generacion_destino_id = null;
        $this->gradosDestino = $this->nivel_destino_id
            ? Grado::query()->where('nivel_id', $this->nivel_destino_id)->orderBy('orden')->orderBy('id')->get()
            : collect();
        $this->semestresDestino = collect();
        $this->generacionesDestino = collect();
        $this->grupos_continuidad = [];
    }

    public function updatedGradoDestinoId(): void
    {
        $this->grado_destino_id = filled($this->grado_destino_id) ? (int) $this->grado_destino_id : null;
        $this->semestre_destino_id = null;
        $nivel = $this->nivel_destino_id ? Nivel::query()->find($this->nivel_destino_id) : null;
        $this->semestresDestino = $nivel?->slug === 'bachillerato' && $this->grado_destino_id
            ? Semestre::query()->where('grado_id', $this->grado_destino_id)->orderBy('orden_global')->orderBy('numero')->get()
            : collect();

        if ($this->semestresDestino->isNotEmpty()) {
            $this->semestre_destino_id = $this->semestresDestino->first()?->id;
        }

        $this->resolverGeneracionDestino();
        $this->cargarGruposDestino();
    }

    public function updatedSemestreDestinoId(): void
    {
        $this->semestre_destino_id = filled($this->semestre_destino_id) ? (int) $this->semestre_destino_id : null;
        $this->resolverGeneracionDestino();
        $this->cargarGruposDestino();
    }

    public function updatedGeneracionDestinoId(): void
    {
        $this->generacion_destino_id = filled($this->generacion_destino_id) ? (int) $this->generacion_destino_id : null;
        $this->cargarGruposDestino();
    }

    public function prepararClasificacion(CierreGeneracionContinuidadService $service): void
    {
        $this->validate([
            'ciclo_origen_id' => ['required', 'exists:ciclo_escolares,id'],
            'generacion_id' => ['required', 'exists:generaciones,id'],
            'grupo_origen_id' => ['nullable', 'exists:grupos,id'],
        ]);

        $this->alumnos = $service->candidatos(
            $this->nivel->id,
            (int) $this->ciclo_origen_id,
            (int) $this->generacion_id,
            $this->grupo_origen_id,
        )->all();

        if ($this->alumnos === []) {
            $this->addError('generacion_id', 'No se encontraron alumnos en el ciclo, generación y grupo seleccionados.');
            return;
        }

        $this->decisiones = [];
        foreach ($this->alumnos as $alumno) {
            $resultado = $alumno['procesable']
                ? 'pendiente'
                : $this->normalizarResultadoExistente($alumno['resultado_existente']);
            $this->decisiones[$alumno['id']] = [
                'resultado' => $resultado,
                'grupo_destino_id' => null,
                'matricula' => '',
                'motivo' => '',
                'escuela_destino' => '',
            ];
        }

        $this->seleccionados = collect($this->alumnos)
            ->where('procesable', true)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
        $this->proponerDestino($service);
        $this->paso = 2;
        $this->resetValidation();
    }

    public function aplicarResultadoMasivo(): void
    {
        if (! in_array($this->resultado_masivo, CierreGeneracionContinuidadService::RESULTADOS, true)
            || $this->resultado_masivo === 'pendiente') {
            $this->addError('resultado_masivo', 'Selecciona un resultado definitivo válido.');
            return;
        }

        foreach ($this->seleccionados as $id) {
            $id = (int) $id;
            $alumno = collect($this->alumnos)->firstWhere('id', $id);
            if (! $alumno || ! $alumno['procesable']) {
                continue;
            }
            $this->decisiones[$id]['resultado'] = $this->resultado_masivo;
            if ($this->grupo_masivo_id) {
                $this->decisiones[$id]['grupo_destino_id'] = (int) $this->grupo_masivo_id;
            }
        }
    }

    public function seleccionarTodosVisibles(): void
    {
        $this->seleccionados = $this->alumnosFiltrados
            ->where('procesable', true)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    public function limpiarSeleccion(): void
    {
        $this->seleccionados = [];
    }

    public function aplicarGrupoMasivo(): void
    {
        if (! $this->grupo_masivo_id) {
            $this->addError('grupo_masivo_id', 'Selecciona un grupo destino.');
            return;
        }

        $aplicados = 0;
        foreach ($this->seleccionados as $id) {
            $id = (int) $id;
            $alumno = collect($this->alumnos)->firstWhere('id', $id);
            if (! $alumno || ! $alumno['procesable']) {
                continue;
            }

            $compatibles = collect($this->gruposParaAlumno($alumno))->pluck('id')->map(fn ($valor) => (int) $valor);
            if (! $compatibles->contains((int) $this->grupo_masivo_id)) {
                continue;
            }

            $this->decisiones[$id]['grupo_destino_id'] = (int) $this->grupo_masivo_id;
            $aplicados++;
        }

        if ($aplicados === 0) {
            $this->addError('grupo_masivo_id', 'El grupo no es compatible con los alumnos seleccionados que requieren destino.');
            return;
        }

        $this->resetValidation('grupo_masivo_id');
    }

    public function siguiente(): void
    {
        if ($this->paso === 2) {
            $pendientes = collect($this->alumnos)
                ->where('procesable', true)
                ->filter(fn (array $alumno): bool => ($this->decisiones[$alumno['id']]['resultado'] ?? 'pendiente') === 'pendiente');
            if ($pendientes->isNotEmpty()) {
                $this->addError('decisiones', 'Asigna un resultado definitivo a todos los alumnos procesables.');
                return;
            }
            $this->paso = 3;
            $this->cargarGruposDestino();
            return;
        }

        if ($this->paso === 3) {
            $this->validarDestinos();
            $this->paso = 4;
            $this->vista_previa = $this->construirVistaPrevia();
            return;
        }

        if ($this->paso === 4) {
            $this->validate([
                'fecha_efectiva' => ['required', 'date'],
                'motivo' => ['required', 'string', 'min:10', 'max:1500'],
                'cerrar_generacion' => ['boolean'],
            ]);
            $this->paso = 5;
        }
    }

    public function anterior(): void
    {
        $this->paso = max(1, $this->paso - 1);
        $this->resetValidation();
    }

    public function ejecutar(CierreGeneracionContinuidadService $service): void
    {
        $this->validate([
            'confirmacion' => ['required', 'in:CONFIRMAR'],
            'password_confirmacion' => ['required', 'string'],
            'fecha_efectiva' => ['required', 'date'],
            'motivo' => ['required', 'string', 'min:10', 'max:1500'],
        ]);

        if (! Hash::check($this->password_confirmacion, (string) auth()->user()?->password)) {
            $this->addError('password_confirmacion', 'La contraseña no es correcta.');
            return;
        }

        $configuracion = [
            'nivel_id' => $this->nivel->id,
            'ciclo_origen_id' => $this->ciclo_origen_id,
            'generacion_id' => $this->generacion_id,
            'grupo_origen_id' => $this->grupo_origen_id,
            'ciclo_destino_id' => $this->ciclo_destino_id,
            'nivel_destino_id' => $this->nivel_destino_id,
            'grado_destino_id' => $this->grado_destino_id,
            'semestre_destino_id' => $this->semestre_destino_id,
            'generacion_destino_id' => $this->generacion_destino_id,
            'fecha_efectiva' => $this->fecha_efectiva,
            'motivo' => trim($this->motivo),
            'cerrar_generacion' => $this->cerrar_generacion,
        ];

        $proceso = $service->ejecutar($configuracion, $this->decisiones, (int) auth()->id());
        $this->reiniciarAsistente();
        $this->cargarGeneracionesOrigen();
        $this->refrescarProcesos();
        $this->dispatch('proyecciones-actualizadas');

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Cierre procesado',
            'text' => "Se procesaron {$proceso->total_procesados} alumno(s). Las continuidades quedaron pendientes de confirmación y el historial anterior se conservó.",
            'position' => 'top-end',
        ]);
    }

    public function prepararReversion(int $procesoId, CierreGeneracionContinuidadService $service): void
    {
        $proceso = ProcesoCierreCiclo::query()
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($procesoId);
        $bloqueos = $service->bloqueosReversion($proceso);

        if ($bloqueos->isNotEmpty()) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Reversión bloqueada',
                'text' => $bloqueos->take(4)->implode(' '),
                'position' => 'top-end',
            ]);
            return;
        }

        $this->procesoReversionId = $proceso->id;
        $this->motivo_reversion = '';
        $this->modalReversion = true;
    }

    public function revertirProceso(CierreGeneracionContinuidadService $service): void
    {
        $this->validate([
            'procesoReversionId' => ['required', 'exists:procesos_cierre_ciclo,id'],
            'motivo_reversion' => ['required', 'string', 'min:10', 'max:1500'],
            'password_confirmacion' => ['required', 'string'],
        ]);

        if (! Hash::check($this->password_confirmacion, (string) auth()->user()?->password)) {
            $this->addError('password_confirmacion', 'La contraseña no es correcta.');
            return;
        }

        $proceso = ProcesoCierreCiclo::query()
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($this->procesoReversionId);
        $service->revertir($proceso, $this->motivo_reversion, (int) auth()->id());

        $this->modalReversion = false;
        $this->reset(['procesoReversionId', 'motivo_reversion', 'password_confirmacion']);
        $this->cargarGeneracionesOrigen();
        $this->refrescarProcesos();
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Proceso revertido',
            'text' => 'Los alumnos regresaron a su estado anterior. La reversión quedó registrada en la bitácora.',
            'position' => 'top-end',
        ]);
    }

    public function prepararReactivacion(int $generacionId): void
    {
        $generacion = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('status', false)
            ->findOrFail($generacionId);

        $this->generacionReactivarId = $generacion->id;
        $this->motivo_reactivacion = '';
        $this->reactivar_egresados = false;
        $this->modalReactivar = true;
    }

    public function reactivar(GestionAcademicaService $service): void
    {
        $datos = $this->validate([
            'generacionReactivarId' => ['required', 'exists:generaciones,id'],
            'motivo_reactivacion' => ['required', 'string', 'min:10', 'max:1000'],
            'reactivar_egresados' => ['boolean'],
        ]);

        $generacion = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('status', false)
            ->findOrFail($datos['generacionReactivarId']);
        $afectados = $service->reactivarGeneracion(
            $generacion,
            trim($datos['motivo_reactivacion']),
            auth()->id(),
            (bool) $datos['reactivar_egresados'],
        );

        $generacion->update(['estado_cierre' => 'activa']);
        $this->modalReactivar = false;
        $this->reset(['generacionReactivarId', 'motivo_reactivacion']);
        $this->cargarGeneracionesOrigen();
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Generación reabierta',
            'text' => $this->reactivar_egresados
                ? "Se reactivaron {$afectados} alumno(s) para correcciones."
                : 'La generación se reabrió sin alterar el estatus histórico de sus alumnos.',
            'position' => 'top-end',
        ]);
    }

    public function prepararArchivoGeneracion(int $generacionId): void
    {
        $generacion = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('status', false)
            ->findOrFail($generacionId);

        if ($generacion->inscripcionCiclos()->where('estado', 'en_curso')->exists()) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Generación con alumnos pendientes',
                'text' => 'No puede archivarse mientras tenga registros de ciclo en curso.',
                'position' => 'top-end',
            ]);
            return;
        }

        $this->generacionArchivarId = $generacion->id;
        $this->motivo_archivo = '';
        $this->confirmacion_archivo = '';
        $this->password_archivo = '';
        $this->modalArchivar = true;
    }

    public function archivarGeneracion(): void
    {
        $datos = $this->validate([
            'generacionArchivarId' => ['required', 'exists:generaciones,id'],
            'motivo_archivo' => ['required', 'string', 'min:10', 'max:1000'],
            'confirmacion_archivo' => ['required', 'in:ARCHIVAR'],
            'password_archivo' => ['required', 'string'],
        ]);

        if (! Hash::check($datos['password_archivo'], (string) auth()->user()?->password)) {
            $this->addError('password_archivo', 'La contraseña no es correcta.');
            return;
        }

        DB::transaction(function () use ($datos): void {
            $generacion = Generacion::query()
                ->where('nivel_id', $this->nivel->id)
                ->lockForUpdate()
                ->findOrFail((int) $datos['generacionArchivarId']);

            if ($generacion->inscripcionCiclos()->where('estado', 'en_curso')->exists()) {
                throw ValidationException::withMessages([
                    'motivo_archivo' => 'La generación todavía tiene alumnos con ciclo en curso.',
                ]);
            }

            $antes = $generacion->only([
                'status', 'estado_cierre', 'cerrada_at', 'cerrada_por', 'archivada_at', 'archivada_por', 'observaciones',
            ]);
            $generacion->update([
                'status' => false,
                'estado_cierre' => 'archivada',
                'archivada_at' => now(),
                'archivada_por' => auth()->id(),
                'observaciones' => trim($datos['motivo_archivo']),
            ]);

            CambioAcademico::query()->create([
                'generacion_id' => $generacion->id,
                'tipo' => 'archivo_generacion',
                'motivo' => trim($datos['motivo_archivo']),
                'datos_anteriores' => $antes,
                'datos_nuevos' => $generacion->fresh()->only([
                    'status', 'estado_cierre', 'cerrada_at', 'cerrada_por', 'archivada_at', 'archivada_por', 'observaciones',
                ]),
                'realizado_por' => auth()->id(),
                'realizado_at' => now(),
            ]);
        });

        $this->modalArchivar = false;
        $this->reset(['generacionArchivarId', 'motivo_archivo', 'confirmacion_archivo', 'password_archivo']);
        $this->cargarGeneracionesOrigen();
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Generación archivada',
            'text' => 'Permanece disponible en consultas históricas y ya no se ofrece para asignaciones nuevas.',
            'position' => 'top-end',
        ]);
    }

    public function alternarDetallesProceso(int $procesoId): void
    {
        ProcesoCierreCiclo::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('tipo', 'cierre_nivel_continuidad')
            ->findOrFail($procesoId);

        $this->procesoExpandidoId = $this->procesoExpandidoId === $procesoId ? null : $procesoId;
    }

    public function getDetallesProcesoProperty(): Collection
    {
        if (! $this->procesoExpandidoId) {
            return collect();
        }

        $procesoValido = ProcesoCierreCiclo::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('tipo', 'cierre_nivel_continuidad')
            ->whereKey($this->procesoExpandidoId)
            ->exists();

        if (! $procesoValido) {
            return collect();
        }

        return \App\Models\ProcesoCierreCicloDetalle::query()
            ->with(['inscripcion' => fn ($relacion) => $relacion->withTrashed()])
            ->where('proceso_cierre_ciclo_id', $this->procesoExpandidoId)
            ->where('resultado', '!=', 'sin_cambio')
            ->orderBy('resultado')
            ->orderBy('id')
            ->get();
    }

    public function getAlumnosFiltradosProperty(): Collection
    {
        $buscar = mb_strtolower(trim($this->buscar));

        return collect($this->alumnos)
            ->filter(function (array $alumno) use ($buscar): bool {
                if ($buscar !== '') {
                    $texto = mb_strtolower($alumno['nombre'].' '.$alumno['matricula'].' '.$alumno['curp']);
                    if (! str_contains($texto, $buscar)) {
                        return false;
                    }
                }
                if ($this->filtro_estatus !== '' && $alumno['estatus'] !== $this->filtro_estatus) {
                    return false;
                }
                if ($this->filtro_resultado !== ''
                    && ($this->decisiones[$alumno['id']]['resultado'] ?? '') !== $this->filtro_resultado) {
                    return false;
                }
                return true;
            })
            ->values();
    }

    public function getGruposMasivosProperty(): Collection
    {
        return collect($this->grupos_continuidad)
            ->concat(collect($this->grupos_repeticion)->flatten(1))
            ->unique('id')
            ->sortBy('label')
            ->values();
    }

    public function getProcesosRecientesProperty(): Collection
    {
        return ProcesoCierreCiclo::query()
            ->with(['generacion:id,nombre,anio_ingreso,anio_egreso', 'cicloEscolar:id,inicio_anio,fin_anio', 'cicloDestino:id,inicio_anio,fin_anio', 'usuarioRealizo:id,name'])
            ->where('nivel_id', $this->nivel->id)
            ->where('tipo', 'cierre_nivel_continuidad')
            ->latest('id')
            ->limit(12)
            ->get();
    }

    public function gruposParaAlumno(array $alumno): array
    {
        $resultado = $this->decisiones[$alumno['id']]['resultado'] ?? 'pendiente';
        if ($resultado === 'continuidad_interna') {
            return $this->grupos_continuidad;
        }
        if ($resultado === 'no_promovido') {
            $llave = $this->llaveRepeticion($alumno);
            return $this->grupos_repeticion[$llave] ?? [];
        }
        return [];
    }

    private function proponerDestino(CierreGeneracionContinuidadService $service): void
    {
        $ciclo = $this->ciclo_origen_id ? CicloEscolar::query()->find($this->ciclo_origen_id) : null;
        if (! $ciclo) {
            return;
        }

        $sugerido = $service->destinoSugerido($this->nivel, $ciclo);
        $this->ciclo_destino_id = $sugerido['ciclo_destino_id'];
        $this->nivel_destino_id = $sugerido['nivel_destino_id'];
        $this->grado_destino_id = $sugerido['grado_destino_id'];
        $this->semestre_destino_id = $sugerido['semestre_destino_id'];
        $this->generacion_destino_id = $sugerido['generacion_destino_id'];
        $this->generacion_esperada = (string) ($sugerido['generacion_esperada'] ?? '');

        $this->gradosDestino = $this->nivel_destino_id
            ? Grado::query()->where('nivel_id', $this->nivel_destino_id)->orderBy('orden')->orderBy('id')->get()
            : collect();
        $this->semestresDestino = $this->grado_destino_id
            ? Semestre::query()->where('grado_id', $this->grado_destino_id)->orderBy('orden_global')->orderBy('numero')->get()
            : collect();
        $this->cargarGeneracionesDestino();
        $this->cargarGruposDestino();
    }

    private function resolverGeneracionDestino(): void
    {
        if (! $this->ciclo_destino_id || ! $this->nivel_destino_id || ! $this->grado_destino_id) {
            $this->generacion_destino_id = null;
            $this->generacion_esperada = '';
            $this->generacionesDestino = collect();
            return;
        }

        $ciclo = CicloEscolar::query()->find($this->ciclo_destino_id);
        $nivel = Nivel::query()->find($this->nivel_destino_id);
        $grado = Grado::query()->find($this->grado_destino_id);
        $semestre = $this->semestre_destino_id ? Semestre::query()->find($this->semestre_destino_id) : null;
        if (! $ciclo || ! $nivel || ! $grado || ($nivel->slug === 'bachillerato' && ! $semestre)) {
            return;
        }

        $asignacion = app(AsignacionEscolarService::class);
        $this->generacion_esperada = $asignacion->etiquetaGeneracionEsperada($ciclo, $nivel, $grado, $semestre);
        $generacion = $asignacion->resolverGeneracion($ciclo, $nivel, $grado, $semestre);
        $this->cargarGeneracionesDestino();
        if ($generacion) {
            $this->generacion_destino_id = $generacion->id;
        }
    }

    private function cargarGeneracionesDestino(): void
    {
        $this->generacionesDestino = $this->nivel_destino_id
            ? Generacion::query()
                ->where('nivel_id', $this->nivel_destino_id)
                ->where('status', true)
                ->orderByDesc('anio_ingreso')
                ->get()
            : collect();
    }

    private function cargarGruposDestino(): void
    {
        $service = app(CierreGeneracionContinuidadService::class);
        $this->grupos_continuidad = $service->gruposContinuidad([
            'ciclo_destino_id' => $this->ciclo_destino_id,
            'nivel_destino_id' => $this->nivel_destino_id,
            'generacion_destino_id' => $this->generacion_destino_id,
            'grado_destino_id' => $this->grado_destino_id,
            'semestre_destino_id' => $this->semestre_destino_id,
        ])->all();

        $this->grupos_repeticion = [];
        if (! $this->ciclo_destino_id || ! $this->generacion_id) {
            return;
        }

        foreach (collect($this->alumnos)->where('procesable', true) as $alumno) {
            $llave = $this->llaveRepeticion($alumno);
            if (isset($this->grupos_repeticion[$llave])) {
                continue;
            }
            $this->grupos_repeticion[$llave] = $service->gruposRepeticion(
                (int) $this->ciclo_destino_id,
                $this->nivel->id,
                (int) $this->generacion_id,
                (int) $alumno['grado_id'],
                filled($alumno['semestre_id']) ? (int) $alumno['semestre_id'] : null,
            )->all();
        }
    }

    private function validarDestinos(): void
    {
        $continuidad = collect($this->alumnos)->where('procesable', true)
            ->filter(fn (array $alumno): bool => ($this->decisiones[$alumno['id']]['resultado'] ?? '') === 'continuidad_interna');
        $repetidores = collect($this->alumnos)->where('procesable', true)
            ->filter(fn (array $alumno): bool => ($this->decisiones[$alumno['id']]['resultado'] ?? '') === 'no_promovido');

        if (($continuidad->isNotEmpty() || $repetidores->isNotEmpty()) && ! $this->ciclo_destino_id) {
            throw ValidationException::withMessages(['ciclo_destino_id' => 'Selecciona el ciclo destino consecutivo.']);
        }

        if ($continuidad->isNotEmpty()) {
            $this->validate([
                'nivel_destino_id' => ['required', 'exists:niveles,id'],
                'grado_destino_id' => ['required', 'exists:grados,id'],
                'generacion_destino_id' => ['required', 'exists:generaciones,id'],
                'semestre_destino_id' => [($this->nivelDestinoEsBachillerato() ? 'required' : 'nullable'), 'exists:semestres,id'],
            ]);
        }

        // La continuidad entre niveles se registra como proyección provisional;
        // el grupo puede asignarse hasta confirmar que el alumno regresó.
        // Los no promovidos sí abren inmediatamente el ciclo siguiente y requieren grupo.
        foreach ($repetidores as $alumno) {
            if (blank($this->decisiones[$alumno['id']]['grupo_destino_id'] ?? null)) {
                throw ValidationException::withMessages([
                    "decisiones.{$alumno['id']}.grupo_destino_id" => "Selecciona el grupo destino de {$alumno['nombre']}.",
                ]);
            }
        }
    }

    private function construirVistaPrevia(): array
    {
        $idsProcesables = collect($this->alumnos)
            ->where('procesable', true)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id);
        $decisionesProcesables = $idsProcesables->map(
            fn (int $id): array => $this->decisiones[$id] ?? ['resultado' => 'pendiente']
        );
        $conteos = $decisionesProcesables->countBy('resultado');
        $destino = null;
        if (($conteos['continuidad_interna'] ?? 0) > 0) {
            $nivel = $this->nivel_destino_id ? Nivel::query()->find($this->nivel_destino_id) : null;
            $grado = $this->grado_destino_id ? Grado::query()->find($this->grado_destino_id) : null;
            $generacion = $this->generacion_destino_id ? Generacion::query()->find($this->generacion_destino_id) : null;
            $destino = trim(implode(' · ', array_filter([
                $nivel?->nombre,
                $grado?->nombre,
                $generacion?->etiqueta,
            ])));
        }

        return [
            'total' => collect($this->alumnos)->where('procesable', true)->count(),
            'conteos' => $conteos->all(),
            'destino' => $destino,
            'fecha' => $this->fecha_efectiva,
            'cerrar_generacion' => $this->cerrar_generacion,
        ];
    }

    private function cargarGeneracionesOrigen(): void
    {
        if (! $this->ciclo_origen_id) {
            $this->generaciones = collect();
            return;
        }

        $this->generaciones = Generacion::query()
            ->withCount([
                'inscripcionCiclos as alumnos_ciclo_count' => fn (Builder $query) => $query
                    ->where('ciclo_escolar_id', $this->ciclo_origen_id),
                'inscripcionCiclos as pendientes_ciclo_count' => fn (Builder $query) => $query
                    ->where('ciclo_escolar_id', $this->ciclo_origen_id)
                    ->where('estado', 'en_curso'),
            ])
            ->where('nivel_id', $this->nivel->id)
            ->whereHas('inscripcionCiclos', fn (Builder $query) => $query->where('ciclo_escolar_id', $this->ciclo_origen_id))
            ->orderByDesc('status')
            ->orderByDesc('anio_ingreso')
            ->get();
    }

    private function cargarGruposOrigen(): void
    {
        if (! $this->ciclo_origen_id || ! $this->generacion_id) {
            $this->gruposOrigen = collect();
            return;
        }

        $ciclosIds = DB::table('inscripcion_ciclos')
            ->where('ciclo_escolar_id', $this->ciclo_origen_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('generacion_id', $this->generacion_id)
            ->pluck('id');

        $idsResumen = DB::table('inscripcion_ciclos')
            ->whereIn('id', $ciclosIds)
            ->pluck('grupo_id');
        $idsAsignaciones = DB::table('inscripcion_ciclo_asignaciones')
            ->whereIn('inscripcion_ciclo_id', $ciclosIds)
            ->where('es_actual', true)
            ->pluck('grupo_id');
        $ids = $idsResumen->concat($idsAsignaciones)->filter()->unique()->values();

        $this->gruposOrigen = Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->whereIn('id', $ids)
            ->orderBy('asignacion_grupo_id')
            ->get();
    }

    private function reiniciarAsistente(bool $volverPasoUno = true): void
    {
        $this->alumnos = [];
        $this->decisiones = [];
        $this->seleccionados = [];
        $this->grupos_continuidad = [];
        $this->grupos_repeticion = [];
        $this->vista_previa = [];
        $this->buscar = '';
        $this->filtro_resultado = '';
        $this->filtro_estatus = '';
        $this->resultado_masivo = 'continuidad_interna';
        $this->grupo_masivo_id = null;
        $this->cerrar_generacion = $this->grupo_origen_id === null;
        $this->motivo = '';
        $this->confirmacion = '';
        $this->password_confirmacion = '';
        $this->fecha_efectiva = now()->toDateString();
        if ($volverPasoUno) {
            $this->paso = 1;
        }
        $this->resetValidation();
    }

    private function refrescarProcesos(): void
    {
        // Los procesos recientes se consultan mediante una propiedad calculada en cada render.
    }

    private function nivelDestinoEsBachillerato(): bool
    {
        return $this->nivel_destino_id
            && Nivel::query()->whereKey($this->nivel_destino_id)->where('slug', 'bachillerato')->exists();
    }

    private function llaveRepeticion(array $alumno): string
    {
        return (int) $alumno['grado_id'].'-'.(int) ($alumno['semestre_id'] ?? 0);
    }

    private function normalizarResultadoExistente(?string $resultado): string
    {
        return match ($resultado) {
            'promovido', 'promovido_nivel', 'continuidad' => 'continuidad_interna',
            'trasladado' => 'traslado',
            'baja_definitiva', 'egresado', 'no_promovido' => $resultado,
            default => 'pendiente',
        };
    }

    public function render()
    {
        $generacionReactivar = $this->generacionReactivarId
            ? Generacion::query()->find($this->generacionReactivarId)
            : null;

        return view('livewire.accion.generales.cierre-nivel-continuidad', compact('generacionReactivar'));
    }
}
