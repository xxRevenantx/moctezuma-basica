<?php

namespace App\Livewire\PersonaNivel;

use App\Models\CicloEscolar;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\LiberacionSueldo;
use App\Models\LiberacionSueldoConfiguracion;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\PersonaNivel;
use App\Models\RolePersona;
use App\Services\LiberacionSueldosArchivoService;
use App\Services\LiberacionSueldosService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class LiberacionSueldos extends Component
{
    use WithFileUploads;

    public string $search = '';
    public string $nivelFiltro = '';
    public string $gradoFiltro = '';
    public string $grupoFiltro = '';
    public string $rolFiltro = '';
    public string $historialSearch = '';
    public string $historialNivel = '';
    public string $historialCiclo = '';

    /** @var array<int, int|string> */
    public array $seleccionados = [];

    /** @var array<int|string, array<string, mixed>> */
    public array $firmantes = [];

    public string $fechaDocumento = '';
    public int $quincenaInicio = 13;
    public int $quincenaFin = 14;
    public int $anio = 2026;
    public string $cicloEscolar = '';
    public string $fechaReanudacion = '';
    public ?int $editandoId = null;

    public $logoNuevo = null;
    public $franjaNueva = null;
    public float $franjaAnchoMm = 200;
    public float $franjaAltoMm = 5.5;
    public float $franjaInferiorMm = 4;

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->fechaDocumento = now()->format('Y-m-d');
        $this->anio = (int) now()->year;
        $ciclo = CicloEscolar::query()->where('es_actual', true)->first()
            ?: CicloEscolar::query()->latest('id')->first();
        $this->cicloEscolar = (string) ($ciclo?->nombre ?: (now()->year - 1) . '-' . now()->year);

        $reanudacion = now()->copy()->month(8)->day(24);
        if ($reanudacion->isPast()) {
            $reanudacion->addYear();
        }
        $this->fechaReanudacion = $reanudacion->format('Y-m-d');

        $config = app(LiberacionSueldosService::class)->configuracion();
        $this->franjaAnchoMm = (float) ($config->franja_ancho_mm ?: 200);
        $this->franjaAltoMm = (float) ($config->franja_alto_mm ?: 5.5);
        $this->franjaInferiorMm = (float) ($config->franja_inferior_mm ?? 4);
    }

    public function updatedNivelFiltro(): void
    {
        $this->gradoFiltro = '';
        $this->grupoFiltro = '';
    }

    public function updatedGradoFiltro(): void
    {
        $this->grupoFiltro = '';
    }

    public function updatedSeleccionados(): void
    {
        $this->seleccionados = collect($this->seleccionados)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->sincronizarFirmantes();
    }

    public function updatedFirmantes($value, string $key): void
    {
        $partes = explode('.', $key);
        if (count($partes) < 2 || ! $value) {
            return;
        }

        $nivelId = (string) $partes[0];
        $campo = (string) end($partes);
        $service = app(LiberacionSueldosService::class);

        if ($campo === 'director_persona_id') {
            $persona = Persona::query()->find((int) $value);
            if ($persona) {
                $this->firmantes[$nivelId]['director_nombre'] = $service->nombrePersona($persona);
                $this->firmantes[$nivelId]['director_cargo'] = $service->cargoDireccion($persona);
            }

            return;
        }

        $nivel = Nivel::query()->with('supervisor')->find((int) $nivelId);
        if (! $nivel) {
            return;
        }

        if ($campo === 'supervisor_director_id') {
            $director = $service->supervisoresNivel($nivel)->firstWhere('id', (int) $value);
            if ($director) {
                $this->firmantes[$nivelId]['supervisor_nombre'] = $service->nombreDirector($director);
                $this->firmantes[$nivelId]['supervisor_cargo'] = Str::upper((string) ($director->cargo ?: 'SUPERVISOR ESCOLAR'));
            }

            return;
        }

        if ($campo === 'jefe_sector_director_id') {
            $director = $service->jefesSector($nivel)->firstWhere('id', (int) $value);
            if ($director) {
                $this->firmantes[$nivelId]['jefe_sector_nombre'] = $service->nombreDirector($director);
                $this->firmantes[$nivelId]['jefe_sector_cargo'] = Str::upper((string) ($director->cargo ?: 'JEFE DE SECTOR'));
            }
        }
    }

    public function seleccionarVisibles(): void
    {
        $this->seleccionados = $this->queryPersonal()
            ->pluck('persona_nivel.id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $this->sincronizarFirmantes();
    }

    public function limpiarSeleccion(): void
    {
        $this->seleccionados = [];
        $this->firmantes = [];
        $this->editandoId = null;
    }

    public function previsualizar(): void
    {
        $documentos = $this->construirDocumentos();
        $token = (string) Str::uuid();

        session()->put("liberacion_sueldos_preview.{$token}", [
            'usuario_id' => auth()->id(),
            'documentos' => $documentos,
        ]);

        $this->dispatch('abrir-url-liberacion', url: route('misrutas.liberacion-sueldos.preview', $token));
    }

    public function generar(string $formato, string $modo = 'masivo'): void
    {
        abort_unless(in_array($formato, ['pdf', 'word', 'zip'], true), 422);

        $documentos = $this->construirDocumentos();
        $service = app(LiberacionSueldosService::class);
        $ids = [];

        DB::transaction(function () use ($documentos, $service, &$ids) {
            foreach ($documentos as $datos) {
                $ids[] = $service->guardar($datos)->id;
            }
        });

        try {
            $archivos = app(LiberacionSueldosArchivoService::class);
            LiberacionSueldo::query()
                ->whereIn('id', $ids)
                ->get()
                ->each(fn (LiberacionSueldo $item) => $archivos->guardar($item));
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('notificar', tipo: 'warning', mensaje: 'El historial fue guardado, pero uno de los archivos se regenerará al descargarlo.');
        }

        if ($formato === 'pdf' && $modo === 'individual' && count($ids) > 1) {
            $formato = 'zip';
        }

        $url = route('misrutas.liberacion-sueldos.descargar', [
            'formato' => $formato,
            'ids' => implode(',', $ids),
        ]);

        $this->dispatch('abrir-url-liberacion', url: $url);
        $this->dispatch('notificar', tipo: 'success', mensaje: count($ids) . ' liberación(es) registrada(s) en el historial.');
    }

    public function editarHistorial(int $id): void
    {
        $liberacion = LiberacionSueldo::query()->findOrFail($id);
        abort_unless($liberacion->persona_nivel_id, 422, 'La asignación original ya no existe.');

        $this->editandoId = $liberacion->id;
        $this->seleccionados = [(int) $liberacion->persona_nivel_id];
        $this->fechaDocumento = $liberacion->fecha_documento->format('Y-m-d');
        $this->quincenaInicio = $liberacion->quincena_inicio;
        $this->quincenaFin = $liberacion->quincena_fin;
        $this->anio = $liberacion->anio;
        $this->cicloEscolar = (string) ($liberacion->ciclo_escolar ?: $this->cicloEscolar);
        $this->fechaReanudacion = $liberacion->fecha_reanudacion?->format('Y-m-d') ?? '';
        $this->firmantes = [
            (string) $liberacion->nivel_id => [
                'director_persona_id' => $liberacion->director_persona_id ?: '',
                'director_nombre' => $liberacion->director_nombre,
                'director_cargo' => $liberacion->director_cargo,
                'supervisor_director_id' => $liberacion->supervisor_director_id ?: '',
                'supervisor_nombre' => $liberacion->supervisor_nombre,
                'supervisor_cargo' => $liberacion->supervisor_cargo,
                'jefe_sector_director_id' => $liberacion->jefe_sector_director_id ?: '',
                'jefe_sector_nombre' => $liberacion->jefe_sector_nombre,
                'jefe_sector_cargo' => $liberacion->jefe_sector_cargo ?: 'JEFE DE SECTOR',
            ],
        ];
        $this->resetValidation();
        $this->dispatch('desplazar-liberacion-formulario');
    }

    public function guardarEdicion(): void
    {
        abort_unless($this->editandoId, 422);
        $documentos = $this->construirDocumentos(true);
        abort_unless(count($documentos) === 1, 422, 'La edición del historial debe realizarse de forma individual.');

        $liberacion = LiberacionSueldo::query()->findOrFail($this->editandoId);
        $archivos = app(LiberacionSueldosArchivoService::class);
        $archivos->eliminar($liberacion);
        $liberacion = app(LiberacionSueldosService::class)->guardar($documentos[0], $liberacion);
        $archivos->guardar($liberacion);

        $this->editandoId = null;
        $this->dispatch('notificar', tipo: 'success', mensaje: 'La liberación fue actualizada correctamente.');
    }

    public function duplicarHistorial(int $id): void
    {
        $original = LiberacionSueldo::query()->findOrFail($id);
        $copia = $original->replicate(['creado_por', 'actualizado_por']);
        $copia->fecha_documento = now()->toDateString();
        $copia->creado_por = auth()->id();
        $copia->actualizado_por = auth()->id();
        $copia->archivo_pdf_path = null;
        $copia->archivo_word_path = null;
        $copia->save();
        app(LiberacionSueldosArchivoService::class)->guardar($copia);

        $this->dispatch('notificar', tipo: 'success', mensaje: 'Se creó una copia editable en el historial.');
    }

    public function eliminarHistorial(int $id): void
    {
        $liberacion = LiberacionSueldo::query()->findOrFail($id);
        app(LiberacionSueldosArchivoService::class)->eliminar($liberacion);
        $liberacion->delete();
        $this->dispatch('notificar', tipo: 'success', mensaje: 'La liberación y sus archivos fueron eliminados del historial.');
    }

    public function guardarLogo(): void
    {
        $this->validate([
            'logoNuevo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ], [
            'logoNuevo.required' => 'Selecciona un logotipo.',
            'logoNuevo.image' => 'El archivo debe ser una imagen válida.',
            'logoNuevo.max' => 'El logotipo no debe superar los 5 MB.',
        ]);

        $config = LiberacionSueldoConfiguracion::query()->firstOrNew();
        $anterior = $config->logo_encabezado_path;
        $config->logo_encabezado_path = $this->logoNuevo->store('liberacion-sueldos/logos', 'public');
        $config->actualizado_por = auth()->id();
        $config->save();

        if ($anterior && $anterior !== $config->logo_encabezado_path) {
            Storage::disk('public')->delete($anterior);
        }

        $this->logoNuevo = null;
        $this->dispatch('notificar', tipo: 'success', mensaje: 'El logotipo del formato fue actualizado.');
    }

    public function restaurarLogo(): void
    {
        $config = LiberacionSueldoConfiguracion::query()->first();
        if ($config?->logo_encabezado_path) {
            Storage::disk('public')->delete($config->logo_encabezado_path);
            $config->logo_encabezado_path = null;
            $config->actualizado_por = auth()->id();
            $config->save();
        }

        $this->dispatch('notificar', tipo: 'success', mensaje: 'Se restauró el logotipo oficial predeterminado.');
    }

    public function guardarFranja(): void
    {
        $this->validate([
            'franjaNueva' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'franjaAnchoMm' => ['required', 'numeric', 'between:50,210'],
            'franjaAltoMm' => ['required', 'numeric', 'between:2,30'],
            'franjaInferiorMm' => ['required', 'numeric', 'between:0,30'],
        ], [
            'franjaNueva.image' => 'La franja debe ser una imagen válida.',
            'franjaNueva.max' => 'La franja no debe superar los 5 MB.',
            'franjaAnchoMm.between' => 'El ancho debe estar entre 50 y 210 mm.',
            'franjaAltoMm.between' => 'El alto debe estar entre 2 y 30 mm.',
            'franjaInferiorMm.between' => 'La distancia inferior debe estar entre 0 y 30 mm.',
        ]);

        if ($this->franjaNueva) {
            $dimensiones = @getimagesize($this->franjaNueva->getRealPath());
            if (! $dimensiones || $dimensiones[0] <= $dimensiones[1]) {
                throw ValidationException::withMessages([
                    'franjaNueva' => 'La franja debe ser una imagen horizontal: el ancho debe ser mayor que el alto.',
                ]);
            }
        }

        $config = LiberacionSueldoConfiguracion::query()->firstOrNew();
        $anterior = $config->franja_inferior_path;

        if ($this->franjaNueva) {
            $config->franja_inferior_path = $this->franjaNueva->store('liberacion-sueldos/franjas', 'public');
        }

        $config->franja_ancho_mm = $this->franjaAnchoMm;
        $config->franja_alto_mm = $this->franjaAltoMm;
        $config->franja_inferior_mm = $this->franjaInferiorMm;
        $config->actualizado_por = auth()->id();
        $config->save();

        if ($this->franjaNueva && $anterior && $anterior !== $config->franja_inferior_path) {
            Storage::disk('public')->delete($anterior);
        }

        $this->franjaNueva = null;
        $this->dispatch('notificar', tipo: 'success', mensaje: 'La franja inferior y sus medidas fueron actualizadas.');
    }

    /**
     * Restaura únicamente campos seguros enviados desde localStorage.
     * Los archivos temporales y el modo de edición no se conservan.
     *
     * @param array<string, mixed> $estado
     */
    public function restaurarEstadoLocal(array $estado): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->search = mb_substr(trim((string) ($estado['search'] ?? '')), 0, 180);
        $this->historialSearch = mb_substr(trim((string) ($estado['historialSearch'] ?? '')), 0, 180);

        $nivelId = $this->idExistente(Nivel::class, $estado['nivelFiltro'] ?? null);
        $this->nivelFiltro = $nivelId ? (string) $nivelId : '';

        $gradoId = $this->idExistente(Grado::class, $estado['gradoFiltro'] ?? null);
        if ($gradoId && $nivelId && ! Grado::query()->whereKey($gradoId)->where('nivel_id', $nivelId)->exists()) {
            $gradoId = null;
        }
        $this->gradoFiltro = $gradoId ? (string) $gradoId : '';

        $grupoId = $this->idExistente(Grupo::class, $estado['grupoFiltro'] ?? null);
        if ($grupoId && $gradoId && ! Grupo::query()->whereKey($grupoId)->where('grado_id', $gradoId)->exists()) {
            $grupoId = null;
        }
        $this->grupoFiltro = $grupoId ? (string) $grupoId : '';

        $rolId = $this->idExistente(RolePersona::class, $estado['rolFiltro'] ?? null);
        $this->rolFiltro = $rolId ? (string) $rolId : '';

        $historialNivelId = $this->idExistente(Nivel::class, $estado['historialNivel'] ?? null);
        $this->historialNivel = $historialNivelId ? (string) $historialNivelId : '';
        $this->historialCiclo = mb_substr(trim((string) ($estado['historialCiclo'] ?? '')), 0, 30);

        $this->fechaDocumento = $this->fechaLocal($estado['fechaDocumento'] ?? null, $this->fechaDocumento);
        $this->fechaReanudacion = $this->fechaLocal($estado['fechaReanudacion'] ?? null, $this->fechaReanudacion, true);
        $this->quincenaInicio = $this->enteroLocal($estado['quincenaInicio'] ?? null, 1, 24, $this->quincenaInicio);
        $this->quincenaFin = $this->enteroLocal($estado['quincenaFin'] ?? null, 1, 24, $this->quincenaFin);
        $this->anio = $this->enteroLocal($estado['anio'] ?? null, 2000, 2100, $this->anio);

        $ciclo = trim((string) ($estado['cicloEscolar'] ?? ''));
        if (preg_match('/^\d{4}-\d{4}$/', $ciclo)) {
            $this->cicloEscolar = $ciclo;
        }

        $this->franjaAnchoMm = $this->decimalLocal($estado['franjaAnchoMm'] ?? null, 50, 210, $this->franjaAnchoMm);
        $this->franjaAltoMm = $this->decimalLocal($estado['franjaAltoMm'] ?? null, 2, 30, $this->franjaAltoMm);
        $this->franjaInferiorMm = $this->decimalLocal($estado['franjaInferiorMm'] ?? null, 0, 30, $this->franjaInferiorMm);

        $ids = collect(is_array($estado['seleccionados'] ?? null) ? $estado['seleccionados'] : [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        $this->seleccionados = PersonaNivel::query()
            ->whereIn('id', $ids)
            ->where('estado', PersonaNivel::ESTADO_ACTIVO)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sortBy(fn (int $id) => $ids->search($id))
            ->values()
            ->all();

        $this->firmantes = $this->firmantesLocales($estado['firmantes'] ?? []);
        $this->sincronizarFirmantes();
        $this->resetValidation();
    }

    private function idExistente(string $modelo, mixed $valor): ?int
    {
        $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id && $modelo::query()->whereKey((int) $id)->exists() ? (int) $id : null;
    }

    private function fechaLocal(mixed $valor, string $predeterminada = '', bool $permitirVacia = false): string
    {
        $fecha = trim((string) $valor);
        if ($permitirVacia && $fecha === '') {
            return '';
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) && strtotime($fecha) !== false
            ? $fecha
            : $predeterminada;
    }

    private function enteroLocal(mixed $valor, int $minimo, int $maximo, int $predeterminado): int
    {
        $entero = filter_var($valor, FILTER_VALIDATE_INT);

        return $entero !== false && $entero >= $minimo && $entero <= $maximo
            ? (int) $entero
            : $predeterminado;
    }

    private function decimalLocal(mixed $valor, float $minimo, float $maximo, float $predeterminado): float
    {
        $numero = filter_var($valor, FILTER_VALIDATE_FLOAT);

        return $numero !== false && $numero >= $minimo && $numero <= $maximo
            ? (float) $numero
            : $predeterminado;
    }

    /** @return array<string, array<string, mixed>> */
    private function firmantesLocales(mixed $firmantes): array
    {
        if (! is_array($firmantes)) {
            return [];
        }

        $campos = [
            'director_persona_id',
            'director_nombre',
            'director_cargo',
            'supervisor_director_id',
            'supervisor_nombre',
            'supervisor_cargo',
            'jefe_sector_director_id',
            'jefe_sector_nombre',
            'jefe_sector_cargo',
        ];

        $resultado = [];
        foreach ($firmantes as $nivelId => $datos) {
            $nivel = filter_var($nivelId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (! $nivel || ! is_array($datos) || ! Nivel::query()->whereKey((int) $nivel)->exists()) {
                continue;
            }

            $resultado[(string) $nivel] = [];
            foreach ($campos as $campo) {
                $valor = $datos[$campo] ?? '';
                if (str_ends_with($campo, '_id')) {
                    $resultado[(string) $nivel][$campo] = is_numeric($valor) && (int) $valor > 0 ? (int) $valor : '';
                } else {
                    $resultado[(string) $nivel][$campo] = mb_substr(trim((string) $valor), 0, 255);
                }
            }
        }

        return $resultado;
    }

    private function sincronizarFirmantes(): void
    {
        $service = app(LiberacionSueldosService::class);
        $niveles = PersonaNivel::query()
            ->with(['nivel.director', 'nivel.supervisor'])
            ->whereIn('id', $this->seleccionados)
            ->get()
            ->pluck('nivel')
            ->filter()
            ->unique('id');

        $nuevos = [];
        foreach ($niveles as $nivel) {
            $key = (string) $nivel->id;
            if (isset($this->firmantes[$key])) {
                $nuevos[$key] = $this->firmantes[$key];
                continue;
            }

            $directores = $service->directoresPlantilla((int) $nivel->id);
            $directorSeleccionado = $directores->count() === 1 ? $directores->first() : null;

            $supervisores = $service->supervisoresNivel($nivel);
            $supervisorSeleccionado = $nivel->supervisor
                ?: ($supervisores->count() === 1 ? $supervisores->first() : null);

            $jefes = $service->jefesSector($nivel);
            $jefeSeleccionado = $jefes->count() === 1 ? $jefes->first() : null;

            $nuevos[$key] = [
                'director_persona_id' => $directorSeleccionado?->id ?: '',
                'director_nombre' => $directorSeleccionado
                    ? $service->nombrePersona($directorSeleccionado)
                    : ($directores->count() > 1 ? '' : $service->nombreDirector($nivel->director)),
                'director_cargo' => $directorSeleccionado
                    ? $service->cargoDireccion($directorSeleccionado, (string) $nivel->nombre)
                    : Str::upper((string) ($nivel->director?->cargo ?: 'DIRECTOR')),
                'supervisor_director_id' => $supervisorSeleccionado?->id ?: '',
                'supervisor_nombre' => $service->nombreDirector($supervisorSeleccionado),
                'supervisor_cargo' => Str::upper((string) ($supervisorSeleccionado?->cargo ?: 'SUPERVISOR ESCOLAR')),
                'jefe_sector_director_id' => $jefeSeleccionado?->id ?: '',
                'jefe_sector_nombre' => $service->nombreDirector($jefeSeleccionado),
                'jefe_sector_cargo' => Str::upper((string) ($jefeSeleccionado?->cargo ?: 'JEFE DE SECTOR')),
            ];
        }

        $this->firmantes = $nuevos;
    }

    /** @return array<int, array<string, mixed>> */
    private function construirDocumentos(bool $permitirInactivo = false): array
    {
        $this->validate([
            'seleccionados' => ['required', 'array', 'min:1'],
            'seleccionados.*' => ['integer', 'exists:persona_nivel,id'],
            'fechaDocumento' => ['required', 'date'],
            'quincenaInicio' => ['required', 'integer', 'between:1,24'],
            'quincenaFin' => ['required', 'integer', 'between:1,24', 'gte:quincenaInicio'],
            'anio' => ['required', 'integer', 'between:2000,2100'],
            'cicloEscolar' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'fechaReanudacion' => ['nullable', 'date'],
            'firmantes.*.director_nombre' => ['required', 'string', 'max:255'],
            'firmantes.*.director_cargo' => ['required', 'string', 'max:120'],
            'firmantes.*.supervisor_nombre' => ['required', 'string', 'max:255'],
            'firmantes.*.supervisor_cargo' => ['required', 'string', 'max:120'],
            'firmantes.*.jefe_sector_nombre' => ['nullable', 'string', 'max:255'],
            'firmantes.*.jefe_sector_cargo' => ['nullable', 'string', 'max:120'],
        ], [
            'seleccionados.required' => 'Selecciona al menos una persona.',
            'quincenaFin.gte' => 'La quincena final debe ser igual o mayor que la inicial.',
            'firmantes.*.director_nombre.required' => 'Captura o selecciona el nombre de dirección.',
            'firmantes.*.supervisor_nombre.required' => 'Captura o selecciona el nombre de supervisión.',
        ]);

        $personasNivelQuery = PersonaNivel::query()
            ->with([
                'persona',
                'nivel.director',
                'nivel.supervisor',
                'detalles.personaRole.rolePersona',
            ])
            ->whereIn('id', $this->seleccionados);

        if (! $permitirInactivo) {
            $personasNivelQuery->where('estado', PersonaNivel::ESTADO_ACTIVO);
        }

        $personasNivel = $personasNivelQuery->get()
            ->sortBy(fn (PersonaNivel $item) => array_search($item->id, $this->seleccionados, true));

        abort_if($personasNivel->count() !== count($this->seleccionados), 422, 'Una de las personas seleccionadas ya no está activa.');

        $service = app(LiberacionSueldosService::class);
        $errores = [];
        foreach ($personasNivel->groupBy('nivel_id') as $nivelId => $personasDelNivel) {
            $nivel = $personasDelNivel->first()?->nivel;
            $datosFirmante = $this->firmantes[(string) $nivelId] ?? [];
            $supervisores = $nivel ? $service->supervisoresNivel($nivel) : collect();

            if ($supervisores->count() > 1 && empty($datosFirmante['supervisor_director_id'])) {
                $errores["firmantes.{$nivelId}.supervisor_director_id"] = 'Hay varios supervisores disponibles. Selecciona el que firmará.';
            }

            $requiereJefeSector = $personasDelNivel->contains(fn (PersonaNivel $item) => $service->esDestinatarioDirectivo($item));
            if ($requiereJefeSector) {
                if (trim((string) ($datosFirmante['jefe_sector_nombre'] ?? '')) === '') {
                    $errores["firmantes.{$nivelId}.jefe_sector_nombre"] = 'Para un destinatario directivo debes seleccionar o capturar al jefe de sector.';
                }
                if (trim((string) ($datosFirmante['jefe_sector_cargo'] ?? '')) === '') {
                    $errores["firmantes.{$nivelId}.jefe_sector_cargo"] = 'Captura el cargo del jefe o jefa de sector.';
                }
            }
        }

        if ($errores) {
            throw ValidationException::withMessages($errores);
        }

        $formulario = [
            'fecha_documento' => $this->fechaDocumento,
            'quincena_inicio' => $this->quincenaInicio,
            'quincena_fin' => $this->quincenaFin,
            'anio' => $this->anio,
            'ciclo_escolar' => $this->cicloEscolar,
            'fecha_reanudacion' => $this->fechaReanudacion ?: null,
        ];

        return $personasNivel
            ->map(fn (PersonaNivel $item) => $service->construirDatos(
                $item,
                $formulario,
                $this->firmantes[(string) $item->nivel_id] ?? []
            ))
            ->values()
            ->all();
    }

    private function queryPersonal(): Builder
    {
        return app(LiberacionSueldosService::class)
            ->personalActivoQuery()
            ->when($this->nivelFiltro !== '', fn (Builder $query) => $query->where('nivel_id', $this->nivelFiltro))
            ->when($this->gradoFiltro !== '', fn (Builder $query) => $query->whereHas('detalles', fn (Builder $detalle) => $detalle->where('estado', 'activo')->where('grado_id', $this->gradoFiltro)))
            ->when($this->grupoFiltro !== '', fn (Builder $query) => $query->whereHas('detalles', fn (Builder $detalle) => $detalle->where('estado', 'activo')->where('grupo_id', $this->grupoFiltro)))
            ->when($this->rolFiltro !== '', function (Builder $query) {
                $query->whereHas('detalles', fn (Builder $detalle) => $detalle
                    ->where('estado', 'activo')
                    ->whereHas('personaRole', fn (Builder $personaRole) => $personaRole->where('role_persona_id', $this->rolFiltro)));
            })
            ->when(trim($this->search) !== '', function (Builder $query) {
                $buscar = trim($this->search);
                $query->where(function (Builder $sub) use ($buscar) {
                    $sub->whereHas('persona', function (Builder $persona) use ($buscar) {
                        $persona->where('nombre', 'like', "%{$buscar}%")
                            ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                            ->orWhere('apellido_materno', 'like', "%{$buscar}%");
                    })->orWhereHas('detalles.personaRole.rolePersona', fn (Builder $rol) => $rol->where('nombre', 'like', "%{$buscar}%"));
                });
            })
            ->orderBy('nivel_id')
            ->orderBy('orden')
            ->orderBy('id');
    }

    public function render()
    {
        $service = app(LiberacionSueldosService::class);
        $personal = $this->queryPersonal()->get();
        $niveles = Nivel::query()->orderBy('id')->get();
        $roles = RolePersona::query()->where('status', true)->orderBy('nombre')->get();
        $grados = $this->nivelFiltro !== ''
            ? Grado::query()->where('nivel_id', $this->nivelFiltro)->orderBy('nombre')->get()
            : collect();
        $grupos = $this->gradoFiltro !== ''
            ? Grupo::query()->with('asignacionGrupo')->where('grado_id', $this->gradoFiltro)->get()
            : collect();

        $seleccionadas = PersonaNivel::query()
            ->with(['nivel.supervisor', 'detalles.personaRole.rolePersona'])
            ->whereIn('id', $this->seleccionados)
            ->get();

        $nivelesSeleccionados = $seleccionadas
            ->pluck('nivel')
            ->filter()
            ->unique('id');

        $directoresPorNivel = $nivelesSeleccionados->mapWithKeys(fn (Nivel $nivel) => [
            $nivel->id => $service->directoresPlantilla($nivel->id),
        ]);
        $supervisoresPorNivel = $nivelesSeleccionados->mapWithKeys(fn (Nivel $nivel) => [
            $nivel->id => $service->supervisoresNivel($nivel),
        ]);
        $jefesSectorPorNivel = $nivelesSeleccionados->mapWithKeys(fn (Nivel $nivel) => [
            $nivel->id => $service->jefesSector($nivel),
        ]);
        $directivosSeleccionadosPorNivel = $seleccionadas
            ->groupBy('nivel_id')
            ->map(fn ($items) => $items->filter(fn (PersonaNivel $item) => $service->esDestinatarioDirectivo($item))->count());

        $historial = LiberacionSueldo::query()
            ->with(['creador:id,name', 'nivel:id,nombre'])
            ->when($this->historialNivel !== '', fn (Builder $query) => $query->where('nivel_id', $this->historialNivel))
            ->when($this->historialCiclo !== '', fn (Builder $query) => $query->where('ciclo_escolar', $this->historialCiclo))
            ->when(trim($this->historialSearch) !== '', function (Builder $query) {
                $buscar = trim($this->historialSearch);
                $query->where(function (Builder $sub) use ($buscar) {
                    $sub->where('trabajador_nombre', 'like', "%{$buscar}%")
                        ->orWhere('director_nombre', 'like', "%{$buscar}%")
                        ->orWhere('supervisor_nombre', 'like', "%{$buscar}%")
                        ->orWhere('jefe_sector_nombre', 'like', "%{$buscar}%")
                        ->orWhere('cct', 'like', "%{$buscar}%");
                });
            })
            ->latest()
            ->limit(150)
            ->get();

        $ciclosHistorial = LiberacionSueldo::query()
            ->whereNotNull('ciclo_escolar')
            ->distinct()
            ->orderByDesc('ciclo_escolar')
            ->pluck('ciclo_escolar');
        $config = LiberacionSueldoConfiguracion::query()->first();

        return view('livewire.persona-nivel.liberacion-sueldos', compact(
            'personal',
            'niveles',
            'grados',
            'grupos',
            'roles',
            'nivelesSeleccionados',
            'directoresPorNivel',
            'supervisoresPorNivel',
            'jefesSectorPorNivel',
            'directivosSeleccionadosPorNivel',
            'historial',
            'ciclosHistorial',
            'config'
        ));
    }
}
