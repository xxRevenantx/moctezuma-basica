<?php

namespace App\Livewire\Generales;

use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Semestre;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Credenciales extends Component
{
    public string $slug_nivel = '';

    public $nivel;

    public Collection $generaciones;
    public Collection $grados;
    public Collection $grupos;
    public Collection $semestres;

    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;

    public string $modo_descarga = 'grupo';

    public ?int $alumno_individual_id = null;

    public array $alumnos_seleccionados = [];

    public string $buscar_alumno = '';

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;

        $this->nivel = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('status', 1)
            ->orderByDesc('anio_ingreso')
            ->get([
                'id',
                'nivel_id',
                'anio_ingreso',
                'anio_egreso',
            ]);

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get([
                'id',
                'nivel_id',
                'nombre',
                'orden',
            ]);

        $this->semestres = $this->cargarSemestresIniciales();
        $this->grupos = collect();

        /*
     * Por defecto se deja por grupo como ya lo venías usando.
     * Ahora también existe el modo nivel para descargar todo el nivel.
     */
        $this->modo_descarga = 'grupo';
    }

    private function cargarSemestresIniciales(): Collection
    {
        if (!$this->esBachillerato()) {
            return collect();
        }

        $columnas = ['id'];

        if (Schema::hasColumn('semestres', 'numero')) {
            $columnas[] = 'numero';
        }

        if (Schema::hasColumn('semestres', 'semestre')) {
            $columnas[] = 'semestre';
        }

        if (Schema::hasColumn('semestres', 'grado_id')) {
            $columnas[] = 'grado_id';
        }

        return Semestre::query()
            ->orderBy('id')
            ->get($columnas);
    }

    public function updatedGeneracionId(): void
    {
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->alumno_individual_id = null;
        $this->alumnos_seleccionados = [];

        $this->grupos = collect();
        $this->semestres = $this->cargarSemestresIniciales();
    }

    public function updatedGradoId(): void
    {
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->alumno_individual_id = null;
        $this->alumnos_seleccionados = [];

        $this->cargarSemestresPorGrado();
        $this->cargarGrupos();
    }

    public function updatedSemestreId(): void
    {
        $this->grupo_id = null;
        $this->alumno_individual_id = null;
        $this->alumnos_seleccionados = [];

        $this->cargarGrupos();
    }

    public function updatedGrupoId(): void
    {
        $this->alumno_individual_id = null;
        $this->alumnos_seleccionados = [];
    }

    public function updatedModoDescarga(): void
    {
        $this->alumno_individual_id = null;
        $this->alumnos_seleccionados = [];
        $this->buscar_alumno = '';
    }

    public function updatedBuscarAlumno(): void
    {
        $this->alumno_individual_id = null;

        /*
         * No se limpian los alumnos seleccionados.
         * Esto permite buscar otro alumno, seleccionarlo y conservar
         * los que ya estaban agregados para descargar.
         */
    }

    public function cargarSemestresPorGrado(): void
    {
        if (!$this->esBachillerato()) {
            $this->semestres = collect();

            return;
        }

        $columnas = ['id'];

        if (Schema::hasColumn('semestres', 'numero')) {
            $columnas[] = 'numero';
        }

        if (Schema::hasColumn('semestres', 'semestre')) {
            $columnas[] = 'semestre';
        }

        if (Schema::hasColumn('semestres', 'grado_id')) {
            $columnas[] = 'grado_id';
        }

        $query = Semestre::query();

        if ($this->grado_id && Schema::hasColumn('semestres', 'grado_id')) {
            $query->where('grado_id', $this->grado_id);
        }

        $this->semestres = $query
            ->orderBy('id')
            ->get($columnas);
    }

    public function cargarGrupos(): void
    {
        $this->grupos = collect();

        if (!$this->generacion_id || !$this->grado_id) {
            return;
        }

        if ($this->esBachillerato() && !$this->semestre_id) {
            return;
        }

        $columnas = [
            'grupos.id',
            'grupos.nivel_id',
            'grupos.asignacion_grupo_id',
        ];

        if (Schema::hasColumn('grupos', 'generacion_id')) {
            $columnas[] = 'grupos.generacion_id';
        }

        if (Schema::hasColumn('grupos', 'grado_id')) {
            $columnas[] = 'grupos.grado_id';
        }

        if (Schema::hasColumn('grupos', 'semestre_id')) {
            $columnas[] = 'grupos.semestre_id';
        }

        $query = Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
            ])
            ->leftJoin('asignacion_grupos', 'asignacion_grupos.id', '=', 'grupos.asignacion_grupo_id')
            ->select($columnas)
            ->where('grupos.nivel_id', $this->nivel->id);

        if (Schema::hasColumn('grupos', 'generacion_id')) {
            $query->where('grupos.generacion_id', $this->generacion_id);
        }

        if (Schema::hasColumn('grupos', 'grado_id')) {
            $query->where('grupos.grado_id', $this->grado_id);
        }

        if ($this->esBachillerato() && Schema::hasColumn('grupos', 'semestre_id')) {
            $query->where('grupos.semestre_id', $this->semestre_id);
        }

        if (!$this->esBachillerato() && Schema::hasColumn('grupos', 'semestre_id')) {
            $query->whereNull('grupos.semestre_id');
        }

        $this->grupos = $query
            ->orderBy('asignacion_grupos.nombre')
            ->get();
    }

    #[Computed]
    public function alumnos(): Collection
    {
        $busquedaLimpia = trim($this->buscar_alumno);

        /*
     * Si el modo es nivel, sí se permite consultar alumnos del nivel completo.
     */
        if (
            $busquedaLimpia === ''
            && !$this->generacion_id
            && $this->modo_descarga !== 'nivel'
            && in_array($this->modo_descarga, ['grado', 'grupo', 'semestre', 'individual', 'seleccionados'])
        ) {
            return collect();
        }

        $query = Inscripcion::query()
            ->with([
                'nivel:id,nombre,slug',
                'grado:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
                'grupo.asignacionGrupo:id,nombre',
                'semestre:id',
            ])
            ->where('nivel_id', $this->nivel->id);

        if ($this->generacion_id) {
            $query->where('generacion_id', $this->generacion_id);
        }

        if ($this->grado_id) {
            $query->where('grado_id', $this->grado_id);
        }

        if ($this->grupo_id) {
            $query->where('grupo_id', $this->grupo_id);
        }

        if (
            $this->esBachillerato()
            && $this->semestre_id
            && Schema::hasColumn('inscripciones', 'semestre_id')
        ) {
            $query->where('semestre_id', $this->semestre_id);
        }

        if ($busquedaLimpia !== '') {
            $query->where(function ($consulta) use ($busquedaLimpia) {
                $consulta
                    ->where('matricula', 'like', '%' . $busquedaLimpia . '%')
                    ->orWhere('nombre', 'like', '%' . $busquedaLimpia . '%')
                    ->orWhere('apellido_paterno', 'like', '%' . $busquedaLimpia . '%')
                    ->orWhere('apellido_materno', 'like', '%' . $busquedaLimpia . '%')
                    ->orWhereRaw(
                        "CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?",
                        ['%' . $busquedaLimpia . '%']
                    )
                    ->orWhereRaw(
                        "CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?",
                        ['%' . $busquedaLimpia . '%']
                    );
            });
        }

        return $query
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->limit(500)
            ->get();
    }

    #[Computed]
    public function alumnosSeleccionadosLista(): Collection
    {
        $ids = collect($this->alumnos_seleccionados)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $alumnos = Inscripcion::query()
            ->with([
                'nivel:id,nombre,slug',
                'grado:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
                'grupo.asignacionGrupo:id,nombre',
                'semestre:id',
            ])
            ->where('nivel_id', $this->nivel->id)
            ->whereIn('id', $ids->all())
            ->get();

        /*
         * Se conserva el orden en que fueron agregados.
         */
        return $alumnos
            ->sortBy(fn($alumno) => $ids->search((int) $alumno->id))
            ->values();
    }

    public function quitarAlumnoSeleccionado(int $alumnoId): void
    {
        $this->alumnos_seleccionados = collect($this->alumnos_seleccionados)
            ->map(fn($id) => (int) $id)
            ->reject(fn($id) => $id === $alumnoId)
            ->values()
            ->toArray();
    }

    public function seleccionarTodosVisibles(): void
    {
        $idsVisibles = $this->alumnos
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->toArray();

        $seleccionados = collect($this->alumnos_seleccionados)
            ->map(fn($id) => (int) $id)
            ->values()
            ->toArray();

        $faltantes = array_values(array_diff($idsVisibles, $seleccionados));

        if (count($faltantes) === 0) {
            $this->alumnos_seleccionados = array_values(array_diff($seleccionados, $idsVisibles));

            return;
        }

        $this->alumnos_seleccionados = array_values(array_unique(array_merge($seleccionados, $idsVisibles)));
    }

    public function limpiarSeleccion(): void
    {
        $this->alumnos_seleccionados = [];
        $this->alumno_individual_id = null;
    }

    public function limpiarFiltros(): void
    {
        $this->generacion_id = null;
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->alumno_individual_id = null;
        $this->alumnos_seleccionados = [];
        $this->buscar_alumno = '';

        $this->grupos = collect();
        $this->semestres = $this->cargarSemestresIniciales();

        $this->modo_descarga = $this->esBachillerato() ? 'semestre' : 'grupo';
    }

    public function modosDescarga(): array
    {
        if ($this->esBachillerato()) {
            return [
                'nivel' => 'Por nivel',
                'generacion' => 'Por generación',
                'grado' => 'Por grado',
                'semestre' => 'Por semestre',
                'grupo' => 'Por grupo',
                'individual' => 'Individual',
                'seleccionados' => 'Seleccionados',
            ];
        }

        return [
            'nivel' => 'Por nivel',
            'generacion' => 'Por generación',
            'grado' => 'Por grado',
            'grupo' => 'Por grupo',
            'individual' => 'Individual',
            'seleccionados' => 'Seleccionados',
        ];
    }

    #[Computed]
    public function puedeDescargar(): bool
    {
        if ($this->modo_descarga === 'nivel') {
            return filled($this->nivel?->id);
        }

        if ($this->modo_descarga === 'generacion') {
            return filled($this->generacion_id);
        }

        if ($this->modo_descarga === 'grado') {
            return filled($this->generacion_id) && filled($this->grado_id);
        }

        if ($this->modo_descarga === 'semestre') {
            return $this->esBachillerato()
                && filled($this->generacion_id)
                && filled($this->grado_id)
                && filled($this->semestre_id);
        }

        if ($this->modo_descarga === 'grupo') {
            return filled($this->generacion_id)
                && filled($this->grado_id)
                && (!$this->esBachillerato() || filled($this->semestre_id))
                && filled($this->grupo_id);
        }

        if ($this->modo_descarga === 'individual') {
            return filled($this->alumno_individual_id);
        }

        if ($this->modo_descarga === 'seleccionados') {
            return count($this->alumnos_seleccionados) > 0;
        }

        return false;
    }

    #[Computed]
    public function parametrosDescarga(): array
    {
        return [
            'slug_nivel' => $this->slug_nivel,
            'modo_descarga' => $this->modo_descarga,
            'generacion_id' => $this->generacion_id,
            'grado_id' => $this->grado_id,
            'semestre_id' => $this->semestre_id,
            'grupo_id' => $this->grupo_id,
            'alumno_id' => $this->alumno_individual_id,
            'alumnos' => implode(',', $this->alumnos_seleccionados),
        ];
    }

    #[Computed]
    public function urlDescarga(): ?string
    {
        if (!$this->puedeDescargar) {
            return null;
        }

        return route('generales.credenciales.pdf', $this->parametrosDescarga);
    }

    #[Computed]
    public function generacionSeleccionada(): ?Generacion
    {
        if (!$this->generacion_id) {
            return null;
        }

        return $this->generaciones->firstWhere('id', (int) $this->generacion_id);
    }

    #[Computed]
    public function gradoSeleccionado(): ?Grado
    {
        if (!$this->grado_id) {
            return null;
        }

        return $this->grados->firstWhere('id', (int) $this->grado_id);
    }

    #[Computed]
    public function semestreSeleccionado()
    {
        if (!$this->semestre_id) {
            return null;
        }

        return $this->semestres->firstWhere('id', (int) $this->semestre_id);
    }

    #[Computed]
    public function grupoSeleccionado(): ?Grupo
    {
        if (!$this->grupo_id) {
            return null;
        }

        return $this->grupos->firstWhere('id', (int) $this->grupo_id);
    }

    #[Computed]
    public function textoModoDescarga(): string
    {
        return $this->modosDescarga()[$this->modo_descarga] ?? 'Credenciales';
    }

    public function textoGrupo($grupo): string
    {
        if (!$grupo) {
            return '—';
        }

        return $grupo->asignacionGrupo?->nombre ?? 'Sin grupo';
    }

    public function textoSemestre($semestre): string
    {
        if (!$semestre) {
            return '—';
        }

        if (isset($semestre->numero)) {
            return 'Semestre ' . $semestre->numero;
        }

        if (isset($semestre->semestre)) {
            return $semestre->semestre;
        }

        return 'Semestre ' . $semestre->id;
    }

    public function nombreAlumno($alumno): string
    {
        return trim(
            ($alumno->apellido_paterno ?? '') . ' ' .
                ($alumno->apellido_materno ?? '') . ' ' .
                ($alumno->nombre ?? '')
        );
    }

    public function esBachillerato(): bool
    {
        return ((int) ($this->nivel?->id ?? 0) === 4)
            || ($this->nivel?->slug === 'bachillerato');
    }

    public function render()
    {
        return view('livewire.generales.credenciales');
    }
}
