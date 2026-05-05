<?php

namespace App\Livewire\Accion\Generales;

use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Parcial;
use App\Models\Semestre;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Listas extends Component
{
    public string $slug_nivel = '';

    public $nivel;

    public Collection $generaciones;
    public Collection $grados;
    public Collection $grupos;
    public Collection $semestres;
    public Collection $parciales;

    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;

    public bool $mostrar_motivo = false;

    public string $tipo_descarga = 'evaluacion';
    public string $opcion_descarga = 'primer_periodo';

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
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso']);

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);

        $this->semestres = $this->cargarSemestresIniciales();

        $this->parciales = $this->cargarParciales();

        $this->grupos = collect();

        /*
        |--------------------------------------------------------------------------
        | Valores iniciales según el nivel
        |--------------------------------------------------------------------------
        | En básica inicio con Lista de evaluación.
        | En bachillerato oculto evaluación/asistencia y arranco con Lista de grupo.
        */
        $this->tipo_descarga = $this->esBachillerato() ? 'grupo' : 'evaluacion';

        $opciones = $this->opcionesDescarga();

        $this->opcion_descarga = array_key_first($opciones) ?? '';
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

    private function cargarParciales(): Collection
    {
        if (!$this->esBachillerato()) {
            return collect();
        }

        return Parcial::query()
            ->orderBy('id')
            ->get(['id', 'parcial', 'descripcion']);
    }

    public function updatedGeneracionId(): void
    {
        $this->grupo_id = null;

        $this->cargarGrupos();
    }

    public function updatedGradoId(): void
    {
        $this->semestre_id = null;
        $this->grupo_id = null;

        $this->cargarSemestresPorGrado();
        $this->cargarGrupos();
    }

    public function updatedSemestreId(): void
    {
        $this->grupo_id = null;

        $this->cargarGrupos();
    }

    public function updatedTipoDescarga(): void
    {
        if (($this->esBachillerato() || $this->esSecundaria()) && in_array($this->tipo_descarga, ['evaluacion', 'asistencia'])) {
            $this->tipo_descarga = 'grupo';
        }

        $this->opcion_descarga = array_key_first($this->opcionesDescarga()) ?? '';
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

        /*
        |--------------------------------------------------------------------------
        | Filtro de semestres
        |--------------------------------------------------------------------------
        | Si la tabla tiene grado_id, filtro los semestres por el grado seleccionado.
        */
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

        $columnas = ['id', 'nivel_id', 'nombre'];

        if (Schema::hasColumn('grupos', 'generacion_id')) {
            $columnas[] = 'generacion_id';
        }

        if (Schema::hasColumn('grupos', 'grado_id')) {
            $columnas[] = 'grado_id';
        }

        if (Schema::hasColumn('grupos', 'semestre_id')) {
            $columnas[] = 'semestre_id';
        }

        $query = Grupo::query()
            ->where('nivel_id', $this->nivel->id);

        if (Schema::hasColumn('grupos', 'generacion_id')) {
            $query->where('generacion_id', $this->generacion_id);
        }

        if (Schema::hasColumn('grupos', 'grado_id')) {
            $query->where('grado_id', $this->grado_id);
        }

        if ($this->esBachillerato() && Schema::hasColumn('grupos', 'semestre_id')) {
            $query->where('semestre_id', $this->semestre_id);
        }

        $this->grupos = $query
            ->orderBy('nombre')
            ->get($columnas);
    }

    public function limpiarFiltros(): void
    {
        $this->generacion_id = null;
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;

        $this->tipo_descarga = $this->esBachillerato() ? 'grupo' : 'evaluacion';

        $opciones = $this->opcionesDescarga();

        $this->opcion_descarga = array_key_first($opciones) ?? '';

        $this->grupos = collect();

        $this->mostrar_motivo = false;
        $this->semestres = $this->cargarSemestresIniciales();
        $this->parciales = $this->cargarParciales();
    }

    public function tiposDescarga(): array
    {
        $tipos = [
            'evaluacion' => 'Lista de evaluación',
            'asistencia' => 'Lista de asistencia',
            'grupo' => 'Lista de grupo',
            'formatos' => 'Formatos',
        ];

        if ($this->esBachillerato() || $this->esSecundaria()) {
            unset($tipos['evaluacion'], $tipos['asistencia']);
        }

        return $tipos;
    }

    public function opcionesDescarga(): array
    {
        /*
        |--------------------------------------------------------------------------
        | Opciones para bachillerato
        |--------------------------------------------------------------------------
        | En bachillerato ya no uso periodos. Uso parciales.
        | Se genera la llave como parcial_ID para validar fácil en el controlador.
        */
        if ($this->esBachillerato() && $this->tipo_descarga !== 'formatos') {
            return $this->parciales
                ->mapWithKeys(function ($parcial) {
                    return [
                        'parcial_' . $parcial->id => $this->textoParcial($parcial),
                    ];
                })
                ->toArray();
        }

        return match ($this->tipo_descarga) {
            'evaluacion' => [
                'primer_periodo' => 'PRIMER PERIODO',
                'segundo_periodo' => 'SEGUNDO PERIODO',
                'tercer_periodo' => 'TERCER PERIODO',
            ],

            'asistencia' => [
                'primer_periodo' => 'PRIMER PERIODO',
                'segundo_periodo' => 'SEGUNDO PERIODO',
                'tercer_periodo' => 'TERCER PERIODO',
            ],

            'grupo' => [
                'primer_periodo' => 'PRIMER PERIODO',
                'segundo_periodo' => 'SEGUNDO PERIODO',
                'tercer_periodo' => 'TERCER PERIODO',
            ],

            'boletas' => [
                'primer_periodo' => 'PRIMER PERIODO',
                'segundo_periodo' => 'SEGUNDO PERIODO',
                'tercer_periodo' => 'TERCER PERIODO',
            ],

            'formatos' => [
                'sece' => 'SECE',
                'sece_interna' => 'SECE interna',
                'personalizadores' => 'Personalizadores',
                'etiquetas' => 'Etiquetas',
            ],

            default => [],
        };
    }

    #[Computed]
    public function puedeDescargar(): bool
    {
        if (!$this->generacion_id) {
            return false;
        }

        if (!$this->grado_id) {
            return false;
        }

        if ($this->esBachillerato() && !$this->semestre_id) {
            return false;
        }

        if (!$this->grupo_id) {
            return false;
        }

        if (!$this->tipo_descarga || !$this->opcion_descarga) {
            return false;
        }

        return true;
    }

    #[Computed]
    public function parametrosDescarga(): array
    {
        return [
            'slug_nivel' => $this->slug_nivel,
            'generacion_id' => $this->generacion_id,
            'grado_id' => $this->grado_id,
            'semestre_id' => $this->semestre_id,
            'grupo_id' => $this->grupo_id,
            'tipo_descarga' => $this->tipo_descarga,
            'opcion_descarga' => $this->opcion_descarga,

            /*
            |--------------------------------------------------------------------------
            | Columna motivo
            |--------------------------------------------------------------------------
            | Solo se manda cuando el documento seleccionado sea Lista de grupo.
            */
            'mostrar_motivo' => $this->tipo_descarga === 'grupo' && $this->mostrar_motivo ? 1 : 0,
        ];
    }

    #[Computed]
    public function urlPdf(): ?string
    {
        if (!$this->puedeDescargar) {
            return null;
        }

        return route('accion.generales.listas.pdf', $this->parametrosDescarga);
    }

    #[Computed]
    public function urlWord(): ?string
    {
        if (!$this->puedeDescargar) {
            return null;
        }

        return route('lista.evaluacion.word', $this->parametrosDescarga);
    }

    #[Computed]
    public function urlDescarga(): ?string
    {
        if (!$this->puedeDescargar) {
            return null;
        }

        if ($this->esWordPreescolarEvaluacion) {
            return $this->urlWord;
        }

        return $this->urlPdf;
    }

    #[Computed]
    public function esWordPreescolarEvaluacion(): bool
    {
        return $this->esPreescolar()
            && $this->tipo_descarga === 'evaluacion';
    }

    #[Computed]
    public function extensionDescarga(): string
    {
        return $this->esWordPreescolarEvaluacion ? 'WORD' : 'PDF';
    }

    #[Computed]
    public function textoBotonDescarga(): string
    {
        return $this->esWordPreescolarEvaluacion ? 'Descargar Word' : 'Descargar PDF';
    }

    #[Computed]
    public function textoModoDescarga(): string
    {
        return $this->esWordPreescolarEvaluacion
            ? 'Este documento se generará en Word porque corresponde a evaluación de preescolar.'
            : 'Este documento se generará en PDF.';
    }

    #[Computed]
    public function generacionSeleccionada(): ?Generacion
    {
        if (!$this->generacion_id) {
            return null;
        }

        return $this->generaciones->firstWhere('id', $this->generacion_id);
    }

    #[Computed]
    public function gradoSeleccionado(): ?Grado
    {
        if (!$this->grado_id) {
            return null;
        }

        return $this->grados->firstWhere('id', $this->grado_id);
    }

    #[Computed]
    public function semestreSeleccionado()
    {
        if (!$this->semestre_id) {
            return null;
        }

        return $this->semestres->firstWhere('id', $this->semestre_id);
    }

    #[Computed]
    public function grupoSeleccionado(): ?Grupo
    {
        if (!$this->grupo_id) {
            return null;
        }

        return $this->grupos->firstWhere('id', $this->grupo_id);
    }

    #[Computed]
    public function textoTipoDescarga(): string
    {
        return $this->tiposDescarga()[$this->tipo_descarga] ?? 'Documento';
    }

    #[Computed]
    public function textoOpcionDescarga(): string
    {
        return $this->opcionesDescarga()[$this->opcion_descarga] ?? '—';
    }

    public function etiquetaOpcionDescarga(): string
    {
        if ($this->tipo_descarga === 'formatos') {
            return 'Formato';
        }

        return $this->esBachillerato() ? 'Parcial' : 'Periodo';
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

    public function textoParcial($parcial): string
    {
        if (!$parcial) {
            return '—';
        }

        if (!empty($parcial->parcial)) {
            return mb_strtoupper($parcial->parcial);
        }

        if (!empty($parcial->descripcion)) {
            return mb_strtoupper($parcial->descripcion);
        }

        return 'PARCIAL ' . $parcial->id;
    }

    public function esPreescolar(): bool
    {
        return ((int) ($this->nivel?->id ?? 0) === 1)
            || ($this->nivel?->slug === 'preescolar');
    }

    public function esBachillerato(): bool
    {
        return ((int) ($this->nivel?->id ?? 0) === 4)
            || ($this->nivel?->slug === 'bachillerato');
    }

    public function esSecundaria(): bool
    {
        return ((int) ($this->nivel?->id ?? 0) === 3)
            || ($this->nivel?->slug === 'secundaria');
    }

    public function render()
    {
        return view('livewire.accion.generales.listas');
    }
}
