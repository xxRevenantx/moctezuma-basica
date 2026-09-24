<?php

namespace App\Livewire\Accion\Generales;

use App\Exports\HorarioCargaDocenteExport;
use App\Models\CicloEscolar;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\HorarioReporteConfiguracion;
use App\Models\HorarioReporteDocenteDato;
use App\Models\Nivel;
use App\Models\Persona;
use App\Services\ContextoCicloEscolarSesion;
use App\Services\HorarioCargaDocenteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class HorariosCargaDocente extends Component
{
    public string $slug_nivel = '';
    public ?Nivel $nivel = null;

    public Collection $ciclosEscolares;
    public Collection $grados;
    public Collection $grupos;
    public Collection $profesores;

    public ?int $ciclo_escolar_id = null;
    public ?int $grado_id = null;
    public ?int $grupo_id = null;
    public ?int $profesor_id = null;

    public string $formato = 'asig';
    public array $configuracion = [];
    public array $datosLaborales = [];
    public ?string $mensaje = null;

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()
            ->with(['director', 'supervisor'])
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        abort_unless($this->nivel->slug === 'secundaria', 404);

        $this->ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->orderByDesc('id')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual', 'cerrado_at']);

        $this->grados = collect();
        $this->grupos = collect();
        $this->profesores = collect();

        $this->ciclo_escolar_id = app(ContextoCicloEscolarSesion::class)->resolver($this->ciclosEscolares);
        $this->cargarCatalogos();
        $this->cargarConfiguracion();
        $this->cargarDatosLaborales();
    }

    public function updatedCicloEscolarId(): void
    {
        app(ContextoCicloEscolarSesion::class)->recordar($this->ciclo_escolar_id);
        $this->grado_id = null;
        $this->grupo_id = null;
        $this->profesor_id = null;
        $this->mensaje = null;
        $this->cargarCatalogos();
        $this->cargarConfiguracion();
        $this->cargarDatosLaborales();
        unset($this->reporte, $this->gruposDisponibles);
    }

    public function updatedGradoId(): void
    {
        if ($this->grupo_id && !$this->gruposDisponibles->contains('id', $this->grupo_id)) {
            $this->grupo_id = null;
        }

        $this->mensaje = null;
        unset($this->reporte, $this->gruposDisponibles);
    }

    public function updatedGrupoId(): void
    {
        $this->mensaje = null;
        unset($this->reporte);
    }

    public function updatedProfesorId(): void
    {
        $this->mensaje = null;
        unset($this->reporte);
    }

    public function cambiarFormato(string $formato): void
    {
        if (!in_array($formato, ['asig', 'general', 'formatos', 'complementarias'], true)) {
            return;
        }

        $this->formato = $formato;
    }

    public function limpiarFiltros(): void
    {
        $this->grado_id = null;
        $this->grupo_id = null;
        $this->profesor_id = null;
        $this->mensaje = null;
        unset($this->reporte, $this->gruposDisponibles);
    }

    public function guardarConfiguracion(): void
    {
        $this->validate([
            'configuracion.escuela' => ['required', 'string', 'max:255'],
            'configuracion.cct' => ['nullable', 'string', 'max:40'],
            'configuracion.zona_escolar' => ['nullable', 'string', 'max:80'],
            'configuracion.turno' => ['nullable', 'string', 'max:80'],
            'configuracion.director' => ['nullable', 'string', 'max:255'],
            'configuracion.supervisor' => ['nullable', 'string', 'max:255'],
        ]);

        HorarioReporteConfiguracion::query()->updateOrCreate(
            [
                'nivel_id' => (int) $this->nivel->id,
                'ciclo_escolar_id' => (int) $this->ciclo_escolar_id,
            ],
            [
                ...$this->configuracion,
                'actualizado_por' => auth()->id(),
            ]
        );

        $this->mensaje = 'Encabezados guardados para el ciclo seleccionado.';
    }

    public function guardarDatosLaborales(): void
    {
        $this->validate([
            'datosLaborales.*.nombramiento' => ['nullable', 'string', 'max:255'],
            'datosLaborales.*.clave_presupuestal' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($this->datosLaborales as $personaId => $datos) {
            $nombramiento = trim((string) ($datos['nombramiento'] ?? ''));
            $clave = trim((string) ($datos['clave_presupuestal'] ?? ''));

            if ($nombramiento === '' && $clave === '') {
                HorarioReporteDocenteDato::query()
                    ->where('nivel_id', $this->nivel->id)
                    ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                    ->where('persona_id', (int) $personaId)
                    ->delete();
                continue;
            }

            HorarioReporteDocenteDato::query()->updateOrCreate(
                [
                    'nivel_id' => (int) $this->nivel->id,
                    'ciclo_escolar_id' => (int) $this->ciclo_escolar_id,
                    'persona_id' => (int) $personaId,
                ],
                [
                    'nombramiento' => $nombramiento !== '' ? $nombramiento : null,
                    'clave_presupuestal' => $clave !== '' ? $clave : null,
                    'actualizado_por' => auth()->id(),
                ]
            );
        }

        $this->mensaje = 'Datos laborales guardados. Los campos vacíos se imprimirán como S/C.';
    }

    #[Computed]
    public function gruposDisponibles(): Collection
    {
        return $this->grupos
            ->when($this->grado_id, fn (Collection $items) => $items->where('grado_id', (int) $this->grado_id))
            ->values();
    }

    #[Computed]
    public function reporte(): ?array
    {
        if (!$this->nivel || !$this->ciclo_escolar_id) {
            return null;
        }

        $ciclo = $this->ciclosEscolares->first(
            fn (CicloEscolar $item) => (int) $item->id === (int) $this->ciclo_escolar_id
        );

        if (!$ciclo) {
            return null;
        }

        return app(HorarioCargaDocenteService::class)->construir(
            nivel: $this->nivel,
            cicloEscolar: $ciclo,
            gradoId: $this->grado_id,
            grupoId: $this->grupo_id,
            profesorId: $this->profesor_id,
        );
    }

    #[Computed]
    public function puedeExportar(): bool
    {
        return $this->reporte !== null
            && (int) ($this->reporte['resumen']['sesiones'] ?? 0) > 0;
    }

    public function descargarExcel()
    {
        abort_unless($this->puedeExportar, 422, 'No hay horarios para exportar con los filtros seleccionados.');

        return Excel::download(
            new HorarioCargaDocenteExport(
                reporte: $this->reporte,
                configuracion: $this->configuracionParaSalida(),
                datosLaborales: $this->datosLaborales,
            ),
            'horarios_carga_docente_secundaria_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function descargarPdfActual()
    {
        return $this->generarPdf([$this->formato], 'formato');
    }

    public function descargarPdfCompleto()
    {
        return $this->generarPdf(['asig', 'general', 'formatos', 'complementarias'], 'completo');
    }

    private function generarPdf(array $formatos, string $sufijo)
    {
        abort_unless($this->puedeExportar, 422, 'No hay horarios para exportar con los filtros seleccionados.');

        $contenido = Pdf::loadView('pdf.horarios-carga-docente', [
            'reporte' => $this->reporte,
            'configuracion' => $this->configuracionParaSalida(),
            'datosLaborales' => $this->datosLaborales,
            'formatos' => $formatos,
        ])->setPaper('letter', 'landscape')->output();

        return response()->streamDownload(function () use ($contenido): void {
            echo $contenido;
        }, 'horarios_carga_docente_' . $sufijo . '_' . now()->format('Ymd_His') . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function cargarCatalogos(): void
    {
        if (!$this->nivel || !$this->ciclo_escolar_id) {
            $this->grados = collect();
            $this->grupos = collect();
            $this->profesores = collect();
            return;
        }

        $this->grupos = Grupo::query()
            ->with(['grado:id,nivel_id,nombre,orden', 'asignacionGrupo:id,nombre'])
            ->where('nivel_id', $this->nivel->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('estado', 'activo')
            ->whereHas('horarios', fn ($query) => $query
                ->where('nivel_id', $this->nivel->id)
                ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                ->where(fn ($actividad) => $actividad
                    ->whereNotNull('asignacion_materia_id')
                    ->orWhereNotNull('taller_sesion_id')))
            ->get()
            ->sortBy(fn (Grupo $grupo) => sprintf(
                '%06d-%s',
                (int) ($grupo->grado?->orden ?? 999999),
                Str::lower(Str::ascii((string) ($grupo->asignacionGrupo?->nombre ?? ''))),
            ))
            ->values();

        $this->grados = $this->grupos
            ->pluck('grado')
            ->filter()
            ->unique('id')
            ->sortBy(fn ($grado) => sprintf(
                '%06d-%s',
                (int) ($grado->orden ?? 999999),
                Str::lower(Str::ascii((string) ($grado->nombre ?? ''))),
            ))
            ->values();

        $horarios = Horario::query()
            ->with([
                'asignacionMateria:id,profesor_id',
                'tallerSesion:id,profesor_id',
            ])
            ->where('nivel_id', $this->nivel->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->whereIn('grupo_id', $this->grupos->pluck('id'))
            ->get(['id', 'grupo_id', 'profesor_id', 'asignacion_materia_id', 'taller_sesion_id']);

        $profesorIds = $horarios
            ->map(function (Horario $horario) {
                return $horario->profesor_id
                    ?: ($horario->taller_sesion_id
                        ? $horario->tallerSesion?->profesor_id
                        : $horario->asignacionMateria?->profesor_id);
            })
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $this->profesores = Persona::query()
            ->whereIn('id', $profesorIds)
            ->get(['id', 'titulo', 'nombre', 'apellido_paterno', 'apellido_materno'])
            ->sortBy(fn (Persona $persona) => Str::lower(Str::ascii($this->nombrePersona($persona))))
            ->values();
    }

    private function cargarConfiguracion(): void
    {
        if (!$this->nivel || !$this->ciclo_escolar_id) {
            $this->configuracion = [];
            return;
        }

        $guardada = HorarioReporteConfiguracion::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->first();

        $this->configuracion = [
            'escuela' => $guardada?->escuela ?: 'CENTRO UNIVERSITARIO MOCTEZUMA A.C.',
            'cct' => $guardada?->cct ?: (string) ($this->nivel->cct ?? ''),
            'zona_escolar' => $guardada?->zona_escolar
                ?: (string) ($this->nivel->supervisor?->zona_escolar ?? $this->nivel->director?->zona_escolar ?? ''),
            'turno' => $guardada?->turno ?: 'MATUTINO',
            'director' => $guardada?->director ?: $this->nombreDirectivo($this->nivel->director),
            'supervisor' => $guardada?->supervisor ?: $this->nombreDirectivo($this->nivel->supervisor),
        ];
    }

    private function cargarDatosLaborales(): void
    {
        $this->datosLaborales = [];

        if (!$this->nivel || !$this->ciclo_escolar_id) {
            return;
        }

        $guardados = HorarioReporteDocenteDato::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->whereIn('persona_id', $this->profesores->pluck('id'))
            ->get()
            ->keyBy('persona_id');

        foreach ($this->profesores as $profesor) {
            $dato = $guardados->get($profesor->id);
            $this->datosLaborales[(int) $profesor->id] = [
                'nombramiento' => (string) ($dato?->nombramiento ?? ''),
                'clave_presupuestal' => (string) ($dato?->clave_presupuestal ?? ''),
            ];
        }
    }

    private function configuracionParaSalida(): array
    {
        return [
            'escuela' => filled($this->configuracion['escuela'] ?? null)
                ? trim((string) $this->configuracion['escuela'])
                : 'CENTRO UNIVERSITARIO MOCTEZUMA A.C.',
            'cct' => filled($this->configuracion['cct'] ?? null) ? trim((string) $this->configuracion['cct']) : 'S/C',
            'zona_escolar' => filled($this->configuracion['zona_escolar'] ?? null) ? trim((string) $this->configuracion['zona_escolar']) : 'S/C',
            'turno' => filled($this->configuracion['turno'] ?? null) ? trim((string) $this->configuracion['turno']) : 'S/C',
            'director' => filled($this->configuracion['director'] ?? null) ? trim((string) $this->configuracion['director']) : 'S/C',
            'supervisor' => filled($this->configuracion['supervisor'] ?? null) ? trim((string) $this->configuracion['supervisor']) : 'S/C',
        ];
    }

    public function etiquetaGrupo(Grupo $grupo): string
    {
        return app(HorarioCargaDocenteService::class)->etiquetaGrupo($grupo);
    }

    public function nombrePersona(Persona $persona): string
    {
        return trim(implode(' ', array_filter([
            $persona->titulo,
            $persona->nombre,
            $persona->apellido_paterno,
            $persona->apellido_materno,
        ])));
    }

    private function nombreDirectivo($persona): string
    {
        if (!$persona) {
            return '';
        }

        return trim(implode(' ', array_filter([
            $persona->titulo ?? null,
            $persona->nombre ?? null,
            $persona->apellido_paterno ?? null,
            $persona->apellido_materno ?? null,
        ])));
    }

    public function render()
    {
        return view('livewire.accion.generales.horarios-carga-docente');
    }
}
