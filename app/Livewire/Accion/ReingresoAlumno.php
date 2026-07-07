<?php

namespace App\Livewire\Accion;

use App\Models\AsignacionMateria;
use App\Models\Ciclo;
use App\Models\CicloEscolar;
use App\Models\DocumentoAlumno;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Periodos;
use App\Models\Semestre;
use App\Models\TipoDocumento;
use App\Models\TrayectoriaAcademica;
use App\Services\CierreNivelReingresoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class ReingresoAlumno extends Component
{
    use WithFileUploads;

    public ?string $slug_nivel = null;
    public string $search = '';
    public ?int $alumno_id = null;
    public string $tipo_retorno = 'reingreso';
    public ?int $ciclo_escolar_id = null;
    public ?int $ciclo_id = null;
    public ?int $nivel_destino_id = null;
    public ?int $grado_destino_id = null;
    public ?int $generacion_destino_id = null;
    public ?int $semestre_destino_id = null;
    public ?int $grupo_destino_id = null;
    public string $fecha_ingreso = '';
    public string $matricula = '';
    public string $escuela_procedencia = '';
    public string $cct_procedencia = '';
    public string $ciclo_procedencia = '';
    public string $ultimo_grado_procedencia = '';
    public string $observaciones_procedencia = '';
    public string $justificacion = '';
    public bool $documentacion_pendiente = true;
    public string $usuario_acceso = '';
    public bool $confirmar = false;
    public $constancia_traslado_pdf;
    public ?int $documento_respaldo_id = null;
    public ?int $alumno_reingresado_id = null;

    public ?int $periodo_externo_id = null;
    public ?int $asignacion_externa_id = null;
    public string $calificacion_externa = '';
    public string $observacion_calificacion = '';

    public Collection $niveles;
    public Collection $ciclosEscolares;
    public Collection $ciclos;

    public function mount(?string $slug_nivel = null): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
        $this->slug_nivel = $slug_nivel;
        $this->niveles = Nivel::query()->orderBy('id')->get(['id', 'nombre', 'slug']);
        $this->ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')->orderByDesc('inicio_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual']);
        $this->ciclos = Ciclo::query()->orderBy('id')->get(['id', 'ciclo']);
        $actual = $this->ciclosEscolares->firstWhere('es_actual', true) ?: $this->ciclosEscolares->first();
        $this->ciclo_escolar_id = $actual?->id;
        $this->ciclo_id = $this->ciclos->first()?->id;
        $this->nivel_destino_id = $this->niveles->firstWhere('slug', $slug_nivel)?->id;
        $this->fecha_ingreso = now()->toDateString();

        $alumnoPreseleccionado = request()->integer('reingreso');
        if ($alumnoPreseleccionado > 0) {
            $this->seleccionarAlumno($alumnoPreseleccionado);
        }
    }

    public function updatedNivelDestinoId(): void
    {
        $this->grado_destino_id = $this->generacion_destino_id = $this->semestre_destino_id = $this->grupo_destino_id = null;
    }

    public function updatedGradoDestinoId(): void
    {
        $this->semestre_destino_id = $this->grupo_destino_id = null;
    }

    public function updatedGeneracionDestinoId(): void
    {
        $this->grupo_destino_id = null;
    }

    public function updatedSemestreDestinoId(): void
    {
        $this->grupo_destino_id = null;
    }

    public function seleccionarAlumno(int $id): void
    {
        $this->alumno_id = $id;
        $alumno = $this->alumnoSeleccionado;
        $ultima = $alumno?->trayectoriasAcademicas?->first();

        if ($ultima) {
            $this->tipo_retorno = $ultima->estatus === 'egresado' ? 'reingreso' : 'reincorporacion';
            $this->nivel_destino_id = $this->tipo_retorno === 'reincorporacion'
                ? $ultima->nivel_id
                : ($this->nivel_destino_id ?: $ultima->nivel_id);
            $this->escuela_procedencia = (string) ($ultima->escuela_procedencia ?? '');
            $this->cct_procedencia = (string) ($ultima->cct_procedencia ?? '');
            $this->ciclo_procedencia = (string) ($ultima->ciclo_procedencia ?? '');
            $this->ultimo_grado_procedencia = (string) ($ultima->ultimo_grado_procedencia ?? '');
        }
        $this->resetValidation();
    }

    public function limpiarAlumno(): void
    {
        $this->alumno_id = null;
        $this->alumno_reingresado_id = null;
        $this->documento_respaldo_id = null;
        $this->constancia_traslado_pdf = null;
        $this->confirmar = false;
    }

    public function getResultadosProperty(): Collection
    {
        $termino = trim($this->search);
        if (mb_strlen($termino) < 2)
            return collect();

        return Inscripcion::withTrashed()
            ->with([
                'trayectoriasAcademicas' => fn($q) => $q->with(['nivel:id,nombre', 'grado:id,nombre', 'generacion:id,anio_ingreso,anio_egreso'])
                    ->orderByDesc('es_actual')->orderByDesc('fecha_inicio')->orderByDesc('id')
            ])
            ->where(function (Builder $q) use ($termino) {
                $like = "%{$termino}%";
                $q->where('matricula', 'like', $like)
                    ->orWhere('curp', 'like', $like)
                    ->orWhereRaw("CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?", [$like]);
            })
            ->whereHas('trayectoriasAcademicas', fn(Builder $q) => $q->whereIn('estatus', [
                'egresado',
                'traslado',
                'baja_temporal',
                'baja_definitiva',
                'inactivo',
                'suspendido',
            ]))
            ->whereDoesntHave('trayectoriasAcademicas', fn(Builder $q) => $q
                ->where('activo', true)
                ->where('es_actual', true)
                ->whereNotIn('estatus', ['egresado', 'traslado', 'baja_definitiva', 'archivado']))
            ->orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombre')
            ->limit(15)->get();
    }

    public function getAlumnoSeleccionadoProperty(): ?Inscripcion
    {
        return $this->alumno_id ? Inscripcion::withTrashed()->with([
            'trayectoriasAcademicas' => fn($q) => $q->with(['nivel:id,nombre', 'grado:id,nombre', 'generacion:id,anio_ingreso,anio_egreso', 'grupo.asignacionGrupo:id,nombre', 'semestre:id,numero'])
                ->orderByDesc('es_actual')->orderByDesc('fecha_inicio')->orderByDesc('id'),
            'documentosActuales.tipoDocumento:id,nombre,slug',
        ])->find($this->alumno_id) : null;
    }

    public function getUltimaTrayectoriaSeleccionadaProperty(): ?TrayectoriaAcademica
    {
        return $this->alumnoSeleccionado?->trayectoriasAcademicas?->first();
    }

    public function getGeneracionDestinoSeleccionadaProperty(): ?Generacion
    {
        return $this->generaciones->firstWhere('id', $this->generacion_destino_id);
    }

    public function ultimaTrayectoriaDe(?Inscripcion $alumno): ?TrayectoriaAcademica
    {
        return $alumno?->trayectoriasAcademicas?->first();
    }

    public function textoGeneracion(?Generacion $generacion): string
    {
        if (!$generacion) {
            return 'Sin generación';
        }

        return $generacion->anio_ingreso . '-' . $generacion->anio_egreso;
    }

    public function getGradosProperty(): Collection
    {
        return $this->nivel_destino_id ? Grado::query()->where('nivel_id', $this->nivel_destino_id)->orderBy('orden')->get(['id', 'nivel_id', 'nombre', 'orden']) : collect();
    }

    public function getGeneracionesProperty(): Collection
    {
        return $this->nivel_destino_id ? Generacion::query()->where('nivel_id', $this->nivel_destino_id)->where('status', true)->orderByDesc('anio_ingreso')->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso']) : collect();
    }

    public function getSemestresProperty(): Collection
    {
        return $this->esBachillerato && $this->grado_destino_id
            ? Semestre::query()->where('grado_id', $this->grado_destino_id)->orderBy('numero')->get(['id', 'grado_id', 'numero'])
            : collect();
    }

    public function getGruposProperty(): Collection
    {
        if (!$this->nivel_destino_id || !$this->grado_destino_id || !$this->generacion_destino_id)
            return collect();
        return Grupo::query()->with('asignacionGrupo:id,nombre')
            ->where('nivel_id', $this->nivel_destino_id)
            ->where('grado_id', $this->grado_destino_id)
            ->where('generacion_id', $this->generacion_destino_id)
            ->when($this->esBachillerato, fn(Builder $q) => $q->where('semestre_id', $this->semestre_destino_id))
            ->when(!$this->esBachillerato, fn(Builder $q) => $q->whereNull('semestre_id'))
            ->orderBy('asignacion_grupo_id')->get();
    }

    public function getEsBachilleratoProperty(): bool
    {
        $nivel = $this->niveles->firstWhere('id', $this->nivel_destino_id);
        return str_contains(mb_strtolower(($nivel?->slug ?? '') . ' ' . ($nivel?->nombre ?? '')), 'bachillerato');
    }

    public function getAsignacionesExternasProperty(): Collection
    {
        $trayectoria = $this->trayectoriaReingresada();
        if (!$trayectoria)
            return collect();

        return AsignacionMateria::query()->with('materia:id,nombre')
            ->where('ciclo_escolar_id', $trayectoria->ciclo_escolar_id)
            ->where('nivel_id', $trayectoria->nivel_id)
            ->where('grado_id', $trayectoria->grado_id)
            ->where('generacion_id', $trayectoria->generacion_id)
            ->where('grupo_id', $trayectoria->grupo_id)
            ->when(
                $trayectoria->semestre_id,
                fn(Builder $q) => $q->where('semestre_id', $trayectoria->semestre_id),
                fn(Builder $q) => $q->whereNull('semestre_id')
            )
            ->where('estado', '!=', 'archivada')->orderBy('orden')->get();
    }

    public function getPeriodosExternosProperty(): Collection
    {
        $trayectoria = $this->trayectoriaReingresada();
        if (!$trayectoria)
            return collect();

        return Periodos::query()->with(['periodoBasica:id,periodo', 'parcialBachillerato:id,parcial', 'cicloEscolar:id,inicio_anio,fin_anio'])
            ->where('ciclo_escolar_id', $trayectoria->ciclo_escolar_id)
            ->where('nivel_id', $trayectoria->nivel_id)
            ->where('generacion_id', $trayectoria->generacion_id)
            ->when(
                $trayectoria->semestre_id,
                fn(Builder $q) => $q->where('semestre_id', $trayectoria->semestre_id),
                fn(Builder $q) => $q->whereNull('semestre_id')
            )
            ->orderBy('fecha_inicio')->get();
    }

    public function confirmarReingreso(): void
    {
        $this->validate([
            'alumno_id' => ['required', 'exists:inscripciones,id'],
            'tipo_retorno' => ['required', 'in:reingreso,reincorporacion'],
            'ciclo_escolar_id' => ['required', 'exists:ciclo_escolares,id'],
            'ciclo_id' => ['required', 'exists:ciclos,id'],
            'nivel_destino_id' => ['required', 'exists:niveles,id'],
            'grado_destino_id' => ['required', 'exists:grados,id'],
            'generacion_destino_id' => ['required', 'exists:generaciones,id'],
            'semestre_destino_id' => [$this->esBachillerato ? 'required' : 'nullable', 'exists:semestres,id'],
            'grupo_destino_id' => ['required', 'exists:grupos,id'],
            'fecha_ingreso' => ['required', 'date'],
            'constancia_traslado_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        if (!$this->confirmar) {
            $this->addError('confirmar', 'Confirma el resumen antes de continuar.');
            return;
        }

        $alumno = Inscripcion::withTrashed()->findOrFail($this->alumno_id);
        try {
            $trayectoria = app(CierreNivelReingresoService::class)->reingresarExalumno(
                $alumno,
                $this->tipo_retorno,
                [
                    'ciclo_escolar_id' => $this->ciclo_escolar_id,
                    'ciclo_id' => $this->ciclo_id,
                    'nivel_id' => $this->nivel_destino_id,
                    'grado_id' => $this->grado_destino_id,
                    'generacion_id' => $this->generacion_destino_id,
                    'semestre_id' => $this->esBachillerato ? $this->semestre_destino_id : null,
                    'grupo_id' => $this->grupo_destino_id,
                    'fecha_ingreso' => $this->fecha_ingreso,
                    'matricula' => trim($this->matricula) ?: null,
                    'justificacion' => trim($this->justificacion) ?: null,
                    'usuario_acceso_activo' => $this->usuario_acceso === '' ? null : $this->usuario_acceso === '1',
                ],
                [
                    'escuela_procedencia' => trim($this->escuela_procedencia) ?: null,
                    'cct_procedencia' => trim($this->cct_procedencia) ?: null,
                    'ciclo_procedencia' => trim($this->ciclo_procedencia) ?: null,
                    'ultimo_grado_procedencia' => trim($this->ultimo_grado_procedencia) ?: null,
                    'observaciones_procedencia' => trim($this->observaciones_procedencia) ?: null,
                    'documentacion_pendiente' => $this->documentacion_pendiente && !$this->constancia_traslado_pdf,
                ],
                auth()->id()
            );

            if ($this->constancia_traslado_pdf) {
                $this->documento_respaldo_id = $this->guardarConstanciaTraslado($alumno, $trayectoria);
                $documento = DocumentoAlumno::query()->findOrFail($this->documento_respaldo_id);
                app(\App\Services\ConstanciaTrasladoService::class)->registrarExterna(
                    $trayectoria,
                    $documento,
                    'Documento externo registrado durante el retorno del alumno.',
                    auth()->id()
                );
                $alumno->update(['documentacion_reingreso_pendiente' => false]);
                $trayectoria->update(['documentacion_pendiente' => false]);
            }

            $this->alumno_reingresado_id = $alumno->id;
            $this->confirmar = false;
            $this->dispatch('swal', [
                'title' => $this->tipo_retorno === 'reingreso' ? 'Exalumno reingresado correctamente.' : 'Alumno reincorporado correctamente.',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $campo => $mensajes)
                $this->addError($campo, $mensajes[0]);
        }
    }

    public function guardarCalificacionExterna(): void
    {
        $this->validate([
            'alumno_reingresado_id' => ['required', 'exists:inscripciones,id'],
            'periodo_externo_id' => ['required', 'exists:periodos,id'],
            'asignacion_externa_id' => ['required', 'exists:asignacion_materias,id'],
            'calificacion_externa' => ['required', 'string', 'max:5'],
        ]);

        app(CierreNivelReingresoService::class)->capturarCalificacionExterna(
            Inscripcion::findOrFail($this->alumno_reingresado_id),
            $this->asignacion_externa_id,
            $this->periodo_externo_id,
            $this->calificacion_externa,
            $this->documento_respaldo_id,
            $this->escuela_procedencia,
            $this->observacion_calificacion,
            auth()->id()
        );

        $this->calificacion_externa = $this->observacion_calificacion = '';
        $this->dispatch('swal', ['title' => 'Calificación externa integrada y marcada como equivalencia.', 'icon' => 'success', 'position' => 'top-end']);
    }

    public function nombreAlumno(?Inscripcion $alumno): string
    {
        return trim(($alumno?->apellido_paterno ?? '') . ' ' . ($alumno?->apellido_materno ?? '') . ' ' . ($alumno?->nombre ?? ''));
    }

    public function textoGrupo($grupo): string
    {
        return $grupo?->asignacionGrupo?->nombre ?? $grupo?->grupo ?? $grupo?->nombre ?? '—';
    }

    public function etiquetaPeriodo($periodo): string
    {
        $nombre = $periodo->periodoBasica?->periodo ?: $periodo->parcialBachillerato?->parcial ?: 'Periodo ' . $periodo->id;
        return $nombre . ($periodo->cicloEscolar ? ' · ' . $periodo->cicloEscolar->nombre : '');
    }

    private function guardarConstanciaTraslado(Inscripcion $alumno, TrayectoriaAcademica $trayectoria): int
    {
        $tipo = TipoDocumento::query()->where('slug', 'constancia-traslado-calificaciones')->firstOrFail();
        $version = ((int) DocumentoAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('tipo_documento_id', $tipo->id)
            ->where('nivel_id', $trayectoria->nivel_id)
            ->max('version')) + 1;

        DocumentoAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('tipo_documento_id', $tipo->id)
            ->where('nivel_id', $trayectoria->nivel_id)
            ->where('es_actual', true)
            ->update(['es_actual' => false, 'estado' => 'reemplazado']);

        $archivo = $this->constancia_traslado_pdf;
        $nombre = Str::uuid() . '.pdf';
        $discoExpedientes = config('filesystems.expedientes_disk', 'local');
        $hashSha256 = hash_file('sha256', $archivo->getRealPath()) ?: null;
        $ruta = $archivo->storeAs("expedientes/{$alumno->id}/constancia-traslado/nivel-{$trayectoria->nivel_id}", $nombre, $discoExpedientes);

        return DocumentoAlumno::query()->create([
            'inscripcion_id' => $alumno->id,
            'tipo_documento_id' => $tipo->id,
            'nivel_id' => $trayectoria->nivel_id,
            'grado_id' => $trayectoria->grado_id,
            'grupo_id' => $trayectoria->grupo_id,
            'ciclo_escolar_id' => $trayectoria->ciclo_escolar_id,
            'trayectoria_academica_id' => $trayectoria->id,
            'fecha_documento' => now()->toDateString(),
            'origen' => 'externo',
            'tipo_movimiento' => $this->tipo_retorno,
            'disco' => $discoExpedientes,
            'ruta' => $ruta,
            'nombre_original' => $archivo->getClientOriginalName(),
            'mime_type' => $archivo->getMimeType() ?: 'application/pdf',
            'tamano_bytes' => $archivo->getSize(),
            'hash_sha256' => $hashSha256,
            'version' => $version,
            'es_actual' => true,
            'estado' => 'validado',
            'observaciones' => 'Documento de procedencia registrado durante el retorno del alumno.',
            'subido_por' => auth()->id(),
            'validado_por' => auth()->id(),
            'validado_at' => now(),
        ])->id;
    }

    private function trayectoriaReingresada(): ?TrayectoriaAcademica
    {
        return $this->alumno_reingresado_id ? TrayectoriaAcademica::query()
            ->where('inscripcion_id', $this->alumno_reingresado_id)
            ->where('es_actual', true)->where('activo', true)->first() : null;
    }

    public function render()
    {
        return view('livewire.accion.reingreso-alumno');
    }
}
