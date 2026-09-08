<?php

namespace App\Livewire\Generales;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Services\ContextoEscolarService;
use App\Services\ContextoCicloEscolarSesion;
use Illuminate\Database\Eloquent\Builder;
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

    public ?int $ciclo_escolar_id = null;
    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;

    public string $modo_descarga = 'grupo';

    public ?int $alumno_individual_id = null;

    public array $alumnos_seleccionados = [];

    public string $buscar_alumno = '';

    public int $copias_por_alumno = 1;

    /** @var array<int|string, int> */
    public array $copias_por_alumno_individual = [];

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;

        $this->nivel = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->orderByDesc('id')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual', 'cerrado_at']);
        $this->ciclo_escolar_id = app(ContextoCicloEscolarSesion::class)->resolver($ciclosEscolares);

        $this->generaciones = collect();
        $this->grados = collect();
        $this->semestres = collect();
        $this->grupos = collect();
        $this->cargarCatalogosContexto();

        /*
     * Por defecto se deja por grupo como ya lo venías usando.
     * Ahora también existe el modo nivel para descargar todo el nivel.
     */
        $this->modo_descarga = 'grupo';
    }

    public function updatedGeneracionId(): void
    {
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->alumno_individual_id = null;
        $this->alumnos_seleccionados = [];
        $this->copias_por_alumno_individual = [];

        $this->cargarGradosContexto();
        $this->semestres = collect();
        $this->grupos = collect();
        $this->sincronizarSeleccionConAlcance();
    }

    public function updatedGradoId(): void
    {
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->alumno_individual_id = null;
        $this->alumnos_seleccionados = [];
        $this->copias_por_alumno_individual = [];

        $this->cargarSemestresPorGrado();
        $this->cargarGrupos();
        $this->sincronizarSeleccionConAlcance();
    }

    public function updatedSemestreId(): void
    {
        $this->grupo_id = null;
        $this->alumno_individual_id = null;
        $this->alumnos_seleccionados = [];
        $this->copias_por_alumno_individual = [];

        $this->cargarGrupos();
        $this->sincronizarSeleccionConAlcance();
    }

    public function updatedGrupoId(): void
    {
        $this->alumno_individual_id = null;
        $this->alumnos_seleccionados = [];
        $this->copias_por_alumno_individual = [];
        $this->sincronizarSeleccionConAlcance();
    }

    public function updatedModoDescarga(): void
    {
        $this->alumno_individual_id = null;
        $this->alumnos_seleccionados = [];
        $this->copias_por_alumno_individual = [];
        $this->buscar_alumno = '';
        $this->sincronizarSeleccionConAlcance();
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

    public function updatedAlumnosSeleccionados($valor = null, $clave = null): void
    {
        $this->depurarCopiasIndividuales();
    }

    public function updatedCopiasPorAlumno($valor): void
    {
        $this->copias_por_alumno = $this->normalizarCopias($valor);

        // Cambiar la cantidad global equivale a volver a aplicar esa cantidad a todos.
        $this->copias_por_alumno_individual = [];
    }

    public function incrementarCopiasAlumno(int $alumnoId): void
    {
        if (! in_array($alumnoId, $this->idsAlumnosSeleccionados(), true)) {
            return;
        }

        $actual = $this->copiasAlumno($alumnoId);
        $this->guardarCopiaIndividual($alumnoId, min($this->maxCopiasPorAlumno(), $actual + 1));
    }

    public function decrementarCopiasAlumno(int $alumnoId): void
    {
        if (! in_array($alumnoId, $this->idsAlumnosSeleccionados(), true)) {
            return;
        }

        $actual = $this->copiasAlumno($alumnoId);
        $this->guardarCopiaIndividual($alumnoId, max(1, $actual - 1));
    }

    public function aplicarCopiasATodosSeleccionados(): void
    {
        $this->copias_por_alumno_individual = [];
    }

    public function cargarSemestresPorGrado(): void
    {
        if (! $this->esBachillerato() || ! $this->ciclo_escolar_id || ! $this->grado_id) {
            $this->semestres = collect();
            return;
        }

        $this->semestres = app(ContextoEscolarService::class)->semestres(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: $this->generacion_id,
            gradoId: $this->grado_id,
        );
    }

    private function cargarCatalogosContexto(): void
    {
        if (! $this->ciclo_escolar_id) {
            $this->generaciones = collect();
            $this->grados = collect();
            $this->semestres = collect();
            $this->grupos = collect();
            return;
        }

        $contexto = app(ContextoEscolarService::class);
        $this->generaciones = $contexto->generaciones(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
        );
        $this->cargarGradosContexto();
    }

    private function cargarGradosContexto(): void
    {
        if (! $this->ciclo_escolar_id) {
            $this->grados = collect();
            return;
        }

        $this->grados = app(ContextoEscolarService::class)->grados(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: $this->generacion_id,
        );
    }

    public function cargarGrupos(): void
    {
        $this->grupos = collect();

        if (! $this->ciclo_escolar_id || ! $this->generacion_id || ! $this->grado_id) {
            return;
        }

        if ($this->esBachillerato() && ! $this->semestre_id) {
            return;
        }

        $this->grupos = app(ContextoEscolarService::class)->grupos(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: $this->generacion_id,
            gradoId: $this->grado_id,
            semestreId: $this->semestre_id,
            bachillerato: $this->esBachillerato(),
        );
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
            ->visiblesEnListas()
            ->with([
                'nivel:id,nombre,slug',
                'grado:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
                'grupo.asignacionGrupo:id,nombre',
                'semestre:id',
            ])
            ->where('nivel_id', $this->nivel->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id);

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
    public function alumnosAlcance(): Collection
    {
        if (! $this->alcanceConfigurado || in_array($this->modo_descarga, ['individual', 'seleccionados'], true)) {
            return collect();
        }

        $query = $this->queryAlumnosAlcance()
            ->with([
                'nivel:id,nombre,slug',
                'grado:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
                'grupo.asignacionGrupo:id,nombre',
                'semestre:id',
            ]);

        $busqueda = trim($this->buscar_alumno);

        if ($busqueda !== '') {
            $query->where(function ($consulta) use ($busqueda) {
                $consulta
                    ->where('matricula', 'like', '%' . $busqueda . '%')
                    ->orWhere('nombre', 'like', '%' . $busqueda . '%')
                    ->orWhere('apellido_paterno', 'like', '%' . $busqueda . '%')
                    ->orWhere('apellido_materno', 'like', '%' . $busqueda . '%')
                    ->orWhereRaw(
                        "CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?",
                        ['%' . $busqueda . '%']
                    )
                    ->orWhereRaw(
                        "CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?",
                        ['%' . $busqueda . '%']
                    );
            });
        }

        return $query
            ->orderBy('grado_id')
            ->orderBy('grupo_id')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();
    }

    #[Computed]
    public function cantidadAlumnosAlcance(): int
    {
        if (! $this->alcanceConfigurado || in_array($this->modo_descarga, ['individual', 'seleccionados'], true)) {
            return 0;
        }

        return $this->queryAlumnosAlcance()->count();
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
            ->visiblesEnListas()
            ->with([
                'nivel:id,nombre,slug',
                'grado:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
                'grupo.asignacionGrupo:id,nombre',
                'semestre:id',
            ])
            ->where('nivel_id', $this->nivel->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
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

        unset($this->copias_por_alumno_individual[$alumnoId]);
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
            $this->depurarCopiasIndividuales();

            return;
        }

        $this->alumnos_seleccionados = array_values(array_unique(array_merge($seleccionados, $idsVisibles)));
        $this->depurarCopiasIndividuales();
    }

    public function seleccionarTodosAlcance(): void
    {
        if (! $this->alcanceConfigurado || in_array($this->modo_descarga, ['individual', 'seleccionados'], true)) {
            return;
        }

        $this->alumnos_seleccionados = $this->queryAlumnosAlcance()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->depurarCopiasIndividuales();
    }

    public function limpiarSeleccion(): void
    {
        $this->alumnos_seleccionados = [];
        $this->copias_por_alumno_individual = [];
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
        $this->copias_por_alumno_individual = [];
        $this->buscar_alumno = '';

        $this->grupos = collect();
        $this->semestres = collect();
        $this->cargarCatalogosContexto();

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
                'seleccionados' => 'Alumnos específicos',
            ];
        }

        return [
            'nivel' => 'Por nivel',
            'generacion' => 'Por generación',
            'grado' => 'Por grado',
            'grupo' => 'Por grupo',
            'individual' => 'Individual',
            'seleccionados' => 'Alumnos específicos',
        ];
    }

    #[Computed]
    public function alcanceConfigurado(): bool
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
                && (! $this->esBachillerato() || filled($this->semestre_id))
                && filled($this->grupo_id);
        }

        if ($this->modo_descarga === 'individual') {
            return filled($this->alumno_individual_id);
        }

        if ($this->modo_descarga === 'seleccionados') {
            return true;
        }

        return false;
    }

    #[Computed]
    public function puedeDescargar(): bool
    {
        if (! $this->alcanceConfigurado) {
            return false;
        }

        if ($this->modo_descarga === 'individual') {
            return filled($this->alumno_individual_id);
        }

        return count($this->idsAlumnosSeleccionados()) > 0;
    }

    #[Computed]
    public function cantidadAlumnosDescarga(): int
    {
        if (! $this->puedeDescargar) {
            return 0;
        }

        return $this->queryAlumnosDescarga()->count();
    }

    #[Computed]
    public function totalCredenciales(): int
    {
        if (! $this->puedeDescargar) {
            return 0;
        }

        if ($this->modo_descarga === 'individual') {
            return $this->cantidadAlumnosDescarga * $this->copias_por_alumno;
        }

        return $this->alumnosSeleccionadosLista
            ->sum(fn ($alumno) => $this->copiasAlumno((int) $alumno->id));
    }

    #[Computed]
    public function tieneCopiasPersonalizadas(): bool
    {
        if ($this->modo_descarga === 'individual') {
            return false;
        }

        return collect($this->copias_por_alumno_individual)
            ->contains(fn ($cantidad, $id) =>
                in_array((int) $id, $this->idsAlumnosSeleccionados(), true)
                && (int) $cantidad !== $this->copias_por_alumno
            );
    }

    #[Computed]
    public function parametrosDescarga(): array
    {
        $seleccion = $this->seleccionDescargaQuery();

        return [
            'slug_nivel' => $this->slug_nivel,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'modo_descarga' => $this->modo_descarga,
            'generacion_id' => $this->generacion_id,
            'grado_id' => $this->grado_id,
            'semestre_id' => $this->semestre_id,
            'grupo_id' => $this->grupo_id,
            'alumno_id' => $this->alumno_individual_id,
            'alumnos' => $seleccion['alumnos'],
            'excluir_alumnos' => $seleccion['excluir_alumnos'],
            'copias_por_alumno' => $this->copias_por_alumno,
            'copias_individuales' => $this->copiasIndividualesQuery(),
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
    public function urlDescargaPng(): ?string
    {
        return $this->puedeDescargar
            ? route('generales.credenciales.imagen', array_merge($this->parametrosDescarga, ['formato' => 'png']))
            : null;
    }

    #[Computed]
    public function urlDescargaJpg(): ?string
    {
        return $this->puedeDescargar
            ? route('generales.credenciales.imagen', array_merge($this->parametrosDescarga, ['formato' => 'jpg']))
            : null;
    }

    #[Computed]
    public function urlVistaPrevia(): ?string
    {
        return $this->puedeDescargar
            ? route('generales.credenciales.preview', $this->parametrosDescarga)
            : null;
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

    public function alumnoSeleccionado(int $alumnoId): bool
    {
        return in_array($alumnoId, $this->idsAlumnosSeleccionados(), true);
    }

    public function copiasAlumno(int $alumnoId): int
    {
        $cantidad = $this->copias_por_alumno_individual[$alumnoId]
            ?? $this->copias_por_alumno_individual[(string) $alumnoId]
            ?? $this->copias_por_alumno;

        return $this->normalizarCopias($cantidad);
    }

    public function maxCopiasPorAlumno(): int
    {
        return max(1, (int) config('credenciales.max_copias_por_alumno', 10));
    }

    public function umbralConfirmacionCopias(): int
    {
        return max(1, (int) config('credenciales.umbral_confirmacion_copias', 100));
    }

    public function maxImagenesPorZip(): int
    {
        return max(1, (int) config('credenciales.max_imagenes_por_zip', 250));
    }

    private function normalizarCopias(mixed $valor): int
    {
        $cantidad = is_numeric($valor) ? (int) $valor : 1;

        return max(1, min($this->maxCopiasPorAlumno(), $cantidad));
    }

    private function guardarCopiaIndividual(int $alumnoId, int $cantidad): void
    {
        $cantidad = $this->normalizarCopias($cantidad);

        if ($cantidad === $this->copias_por_alumno) {
            unset($this->copias_por_alumno_individual[$alumnoId]);
            return;
        }

        $this->copias_por_alumno_individual[$alumnoId] = $cantidad;
    }

    /** @return array<int> */
    private function idsAlumnosSeleccionados(): array
    {
        return collect($this->alumnos_seleccionados)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function depurarCopiasIndividuales(): void
    {
        $ids = $this->idsAlumnosSeleccionados();

        $this->copias_por_alumno_individual = collect($this->copias_por_alumno_individual)
            ->filter(fn ($cantidad, $id) => in_array((int) $id, $ids, true))
            ->map(fn ($cantidad) => $this->normalizarCopias($cantidad))
            ->all();
    }

    private function copiasIndividualesQuery(): string
    {
        if ($this->modo_descarga === 'individual') {
            return '';
        }

        $ids = $this->idsAlumnosSeleccionados();

        return collect($this->copias_por_alumno_individual)
            ->filter(fn ($cantidad, $id) =>
                in_array((int) $id, $ids, true)
                && $this->normalizarCopias($cantidad) !== $this->copias_por_alumno
            )
            ->map(fn ($cantidad, $id) => (int) $id . ':' . $this->normalizarCopias($cantidad))
            ->values()
            ->implode(',');
    }

    /**
     * Mantiene compacta la URL: si casi todos los alumnos están seleccionados,
     * se envían solo los excluidos; si son pocos, se envían solo los incluidos.
     *
     * @return array{alumnos:?string, excluir_alumnos:?string}
     */
    private function seleccionDescargaQuery(): array
    {
        if ($this->modo_descarga === 'individual') {
            return ['alumnos' => null, 'excluir_alumnos' => null];
        }

        $seleccionados = $this->idsAlumnosSeleccionados();

        if ($this->modo_descarga === 'seleccionados') {
            return [
                'alumnos' => $seleccionados === [] ? null : implode(',', $seleccionados),
                'excluir_alumnos' => null,
            ];
        }

        if (! $this->alcanceConfigurado) {
            return ['alumnos' => null, 'excluir_alumnos' => null];
        }

        $idsAlcance = $this->queryAlumnosAlcance()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $seleccionados = array_values(array_intersect($idsAlcance, $seleccionados));
        $excluidos = array_values(array_diff($idsAlcance, $seleccionados));

        if ($excluidos === []) {
            return ['alumnos' => null, 'excluir_alumnos' => null];
        }

        if (count($seleccionados) <= count($excluidos)) {
            return [
                'alumnos' => $seleccionados === [] ? null : implode(',', $seleccionados),
                'excluir_alumnos' => null,
            ];
        }

        return [
            'alumnos' => null,
            'excluir_alumnos' => implode(',', $excluidos),
        ];
    }

    private function queryAlumnosDescarga(): Builder
    {
        $query = $this->queryAlumnosAlcance();

        if ($this->modo_descarga !== 'individual') {
            $ids = $this->idsAlumnosSeleccionados();

            if ($ids === []) {
                return $query->whereRaw('1 = 0');
            }

            $query->whereIn('id', $ids);
        }

        return $query;
    }

    private function queryAlumnosAlcance(): Builder
    {
        $query = Inscripcion::query()
            ->visiblesEnListas()
            ->where('nivel_id', $this->nivel->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id);

        if ($this->modo_descarga === 'generacion') {
            return $query->where('generacion_id', $this->generacion_id);
        }

        if ($this->modo_descarga === 'grado') {
            return $query
                ->where('generacion_id', $this->generacion_id)
                ->where('grado_id', $this->grado_id);
        }

        if ($this->modo_descarga === 'semestre') {
            $query
                ->where('generacion_id', $this->generacion_id)
                ->where('grado_id', $this->grado_id);

            if ($this->esBachillerato() && Schema::hasColumn('inscripciones', 'semestre_id')) {
                $query->where('semestre_id', $this->semestre_id);
            }

            return $query;
        }

        if ($this->modo_descarga === 'grupo') {
            $query
                ->where('generacion_id', $this->generacion_id)
                ->where('grado_id', $this->grado_id)
                ->where('grupo_id', $this->grupo_id);

            if (
                $this->esBachillerato()
                && Schema::hasColumn('inscripciones', 'semestre_id')
                && $this->semestre_id
            ) {
                $query->where('semestre_id', $this->semestre_id);
            }

            return $query;
        }

        if ($this->modo_descarga === 'individual') {
            return $query->whereKey($this->alumno_individual_id);
        }

        if ($this->modo_descarga === 'seleccionados') {
            return $query->whereIn('id', $this->idsAlumnosSeleccionados());
        }

        // modo nivel: solo nivel + ciclo escolar.
        return $query;
    }

    private function sincronizarSeleccionConAlcance(): void
    {
        if (! in_array($this->modo_descarga, ['nivel', 'generacion', 'grado', 'semestre', 'grupo'], true)) {
            return;
        }

        if (! $this->alcanceConfigurado) {
            $this->alumnos_seleccionados = [];
            $this->copias_por_alumno_individual = [];
            return;
        }

        $this->alumnos_seleccionados = $this->queryAlumnosAlcance()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        // Al cambiar el alcance se parte nuevamente de la cantidad global.
        $this->copias_por_alumno_individual = [];
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
