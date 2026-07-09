<?php

namespace App\Livewire\Accion\Generales;

use App\Exports\PromediosMateriasExport;
use App\Models\CampoFormativo;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Nivel;
use App\Services\PromediosTresPeriodosService;
use App\Support\CalificacionBachillerato;
use App\Support\PromedioExcel;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PromediosMaterias extends Component
{
    public string $slug_nivel = '';

    public $nivel;

    public Collection $cicloEscolares;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $grupos;
    public Collection $camposFormativos;

    public string $ciclo_escolar_id = '';
    public string $generacion_id = '';
    public string $grado_id = '';
    public string $grupo_id = '';
    public string $buscar = '';
    public string $alcance_exportacion = 'completo';

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $this->cicloEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->orderByDesc('id')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual']);

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderByDesc('anio_ingreso')
            ->orderByDesc('id')
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso', 'status']);

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);

        $this->camposFormativos = CampoFormativo::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        $this->grupos = collect();
        $actual = $this->cicloEscolares->firstWhere('es_actual', true) ?? $this->cicloEscolares->first();
        $this->ciclo_escolar_id = (string) ($actual?->id ?? '');
        $this->cargarGrupos();
    }

    public function updatedGeneracionId(): void
    {
        $this->grupo_id = '';
        $this->cargarGrupos();
    }

    public function updatedGradoId(): void
    {
        $this->grupo_id = '';
        $this->cargarGrupos();
    }

    public function updatedAlcanceExportacion(): void
    {
        $this->resetErrorBag('alcance_exportacion');
    }

    public function limpiarFiltros(): void
    {
        $actual = $this->cicloEscolares->firstWhere('es_actual', true) ?? $this->cicloEscolares->first();
        $this->ciclo_escolar_id = (string) ($actual?->id ?? '');
        $this->generacion_id = '';
        $this->grado_id = '';
        $this->grupo_id = '';
        $this->buscar = '';
        $this->alcance_exportacion = 'completo';
        $this->resetErrorBag();
        $this->cargarGrupos();
    }

    public function getDisponibleProperty(): bool
    {
        return in_array($this->nivel?->slug, ['primaria', 'secundaria', 'bachillerato'], true);
    }

    public function getReporteProperty(): array
    {
        if (! $this->disponible || $this->ciclo_escolar_id === '') {
            return $this->reporteVacio();
        }

        return app(PromediosTresPeriodosService::class)->generar(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: $this->generacion_id !== '' ? (int) $this->generacion_id : null,
            gradoId: $this->grado_id !== '' ? (int) $this->grado_id : null,
            grupoId: $this->grupo_id !== '' ? (int) $this->grupo_id : null,
        );
    }

    public function getAlumnosFiltradosProperty(): Collection
    {
        $alumnos = collect($this->reporte['alumnos'] ?? []);
        $termino = Str::lower(trim($this->buscar));

        if ($termino === '') {
            return $alumnos;
        }

        return $alumnos
            ->filter(function (array $alumno) use ($termino): bool {
                $texto = Str::lower(implode(' ', [
                    $alumno['matricula'] ?? '',
                    $alumno['alumno'] ?? '',
                    $alumno['grado'] ?? '',
                    $alumno['grupo'] ?? '',
                    collect($alumno['materias'] ?? [])->pluck('materia')->implode(' '),
                ]));

                return str_contains($texto, $termino);
            })
            ->values();
    }

    public function getMateriasClasificacionProperty(): Collection
    {
        return Materia::query()
            ->with('campoFormativo:id,nombre,slug,color_fondo,color_texto')
            ->where('nivel_id', $this->nivel->id)
            ->where('calificable', true)
            ->where('extra', false)
            ->where('receso', false)
            ->orderBy('grado_id')
            ->orderByRaw('semestre_id IS NULL')
            ->orderBy('semestre_id')
            ->orderBy('orden')
            ->orderBy('materia')
            ->get(['id', 'nivel_id', 'grado_id', 'semestre_id', 'campo_formativo_id', 'materia', 'orden']);
    }

    public function actualizarCampoFormativo(int $materiaId, string $campoFormativoId): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $datos = validator(
            ['materia_id' => $materiaId, 'campo_formativo_id' => $campoFormativoId],
            [
                'materia_id' => [
                    'required',
                    Rule::exists('materias', 'id')->where('nivel_id', $this->nivel->id),
                ],
                'campo_formativo_id' => ['required', Rule::exists('campos_formativos', 'id')->where('activo', true)],
            ]
        )->validate();

        Materia::query()
            ->where('id', $datos['materia_id'])
            ->where('nivel_id', $this->nivel->id)
            ->update(['campo_formativo_id' => (int) $datos['campo_formativo_id']]);

        unset($this->reporte, $this->materiasClasificacion);

        $this->dispatch('swal', [
            'title' => 'Campo formativo actualizado',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function exportarExcel(): BinaryFileResponse
    {
        $this->validarExportacion();
        [$gradoId, $grupoId] = $this->filtrosSegunAlcance();

        $reporte = app(PromediosTresPeriodosService::class)->generar(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: $this->generacion_id !== '' ? (int) $this->generacion_id : null,
            gradoId: $gradoId,
            grupoId: $grupoId,
        );

        $ciclo = $reporte['ciclo']['texto'] ?? 'ciclo';
        $nombre = 'PROMEDIOS_TRES_PERIODOS_' . Str::upper(Str::slug($this->nivel->nombre, '_'))
            . '_' . Str::slug($ciclo, '_')
            . '_' . Str::upper($this->alcance_exportacion)
            . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new PromediosMateriasExport($reporte, $this->alcance_exportacion),
            $nombre
        );
    }

    public function getPuedeExportarProperty(): bool
    {
        if ($this->ciclo_escolar_id === '') {
            return false;
        }

        if ($this->alcance_exportacion === 'grado') {
            return $this->grado_id !== '';
        }

        if ($this->alcance_exportacion === 'grupo') {
            return $this->grupo_id !== '';
        }

        return true;
    }

    public function getPdfUrlProperty(): string
    {
        if (! $this->puedeExportar) {
            return '#';
        }

        [$gradoId, $grupoId] = $this->filtrosSegunAlcance(false);

        return route('generales.promedios-materias.pdf', [
            'slug_nivel' => $this->slug_nivel,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'generacion_id' => $this->generacion_id ?: null,
            'grado_id' => $gradoId,
            'grupo_id' => $grupoId,
            'alcance' => $this->alcance_exportacion,
        ]);
    }

    public function formatearPromedio(mixed $valor): string
    {
        return PromedioExcel::formatear($valor, 1, '—');
    }

    public function formatearCalificacionMateria(mixed $valor): string
    {
        return $this->nivel?->slug === 'bachillerato'
            ? CalificacionBachillerato::formatearEntero($valor)
            : PromedioExcel::formatear($valor, 1, '—');
    }

    private function cargarGrupos(): void
    {
        $this->grupos = Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'grado:id,nombre,orden',
                'generacion:id,anio_ingreso,anio_egreso,status',
                'semestre:id,numero',
            ])
            ->where('nivel_id', $this->nivel->id)
            ->when($this->generacion_id !== '', fn ($query) => $query->where('generacion_id', $this->generacion_id))
            ->when($this->grado_id !== '', fn ($query) => $query->where('grado_id', $this->grado_id))
            ->get(['id', 'asignacion_grupo_id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id'])
            ->sort(function (Grupo $a, Grupo $b): int {
                $comparacion = (int) ($a->grado?->orden ?? 999) <=> (int) ($b->grado?->orden ?? 999);
                if ($comparacion !== 0) {
                    return $comparacion;
                }

                $comparacion = (int) ($a->semestre?->numero ?? 999) <=> (int) ($b->semestre?->numero ?? 999);
                if ($comparacion !== 0) {
                    return $comparacion;
                }

                return strnatcasecmp(
                    (string) ($a->asignacionGrupo?->nombre ?? ''),
                    (string) ($b->asignacionGrupo?->nombre ?? '')
                );
            })
            ->values();
    }

    private function validarExportacion(): void
    {
        $reglas = [
            'ciclo_escolar_id' => ['required', Rule::exists('ciclo_escolares', 'id')],
            'alcance_exportacion' => ['required', Rule::in(['completo', 'nivel', 'grado', 'grupo'])],
        ];

        if ($this->alcance_exportacion === 'grado') {
            $reglas['grado_id'] = ['required', Rule::exists('grados', 'id')->where('nivel_id', $this->nivel->id)];
        }

        if ($this->alcance_exportacion === 'grupo') {
            $reglas['grupo_id'] = ['required', Rule::exists('grupos', 'id')->where('nivel_id', $this->nivel->id)];
        }

        $this->validate($reglas, [
            'ciclo_escolar_id.required' => 'Selecciona un ciclo escolar.',
            'grado_id.required' => 'Selecciona un grado para exportar por grado.',
            'grupo_id.required' => 'Selecciona un grupo para exportar por grupo.',
        ]);
    }

    private function filtrosSegunAlcance(bool $estricto = true): array
    {
        return match ($this->alcance_exportacion) {
            'grado' => [
                $this->grado_id !== '' ? (int) $this->grado_id : ($estricto ? null : null),
                null,
            ],
            'grupo' => [
                $this->grado_id !== '' ? (int) $this->grado_id : null,
                $this->grupo_id !== '' ? (int) $this->grupo_id : ($estricto ? null : null),
            ],
            default => [null, null],
        };
    }

    private function reporteVacio(): array
    {
        return [
            'nivel' => ['id' => $this->nivel?->id, 'nombre' => $this->nivel?->nombre, 'slug' => $this->slug_nivel],
            'ciclo' => ['id' => null, 'texto' => '—', 'es_actual' => false],
            'es_bachillerato' => false,
            'etiqueta_evaluaciones' => 'Periodos',
            'resumen' => [
                'turno' => 'MATUTINO',
                'total_alumnos' => 0,
                'total_grupos' => 0,
                'total_bloques' => 0,
                'total_materias' => 0,
                'promedio_general' => null,
                'alumnos_completos' => 0,
                'alumnos_provisionales' => 0,
                'provisional' => true,
            ],
            'bloques' => collect(),
            'grupos' => collect(),
            'alumnos' => collect(),
            'campos' => collect(),
            'nota' => '',
        ];
    }

    public function render()
    {
        return view('livewire.accion.generales.promedios-materias');
    }
}
