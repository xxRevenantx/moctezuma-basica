<?php

namespace App\Livewire\Documentacion;

use App\Models\CartaCompromiso as CartaCompromisoModel;
use App\Models\CartaCompromisoConfiguracion;
use App\Models\CicloEscolar;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Services\HtmlSanitizerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class CartaCompromiso extends Component
{
    use WithPagination;

    public string $modo = 'individual';
    public string $buscar = '';
    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $grupo_id = null;
    public ?int $ciclo_escolar_id = null;

    public ?int $selectedAlumnoId = null;
    public ?array $selectedAlumno = null;
    public array $seleccionados = [];
    public array $tutores = [];
    public array $docentes = [];
    public ?int $tutor_id = null;

    public ?int $editando_id = null;
    public string $folio = '';
    public string $fecha_expedicion = '';
    public string $lugar = 'Cd. Altamirano, Gro.';
    public string $asunto = 'CARTA COMPROMISO';
    public string $leyenda_anual = '';

    public string $membrete_tipo = 'seg';
    public string $encabezado_linea_1 = '';
    public string $encabezado_linea_2 = '';

    public string $destinatario_nombre = '';
    public string $destinatario_cargo = '';
    public string $destinatario_institucion = 'Centro Universitario Moctezuma';

    public string $suscriptor_nombre = '';
    public string $suscriptor_parentesco = '';
    public string $suscriptor_calidad = '';
    public string $referencia_alumno = '';
    public string $grado_texto = '';
    public string $motivo_tipo = 'economicos';
    public string $motivo_texto = 'por motivos económicos';
    public string $contenido_cuerpo = '';

    public string $docente_nombre = '';
    public string $directora_nombre = '';

    public string $buscar_historial = '';
    public string $estado_historial = 'todos';
    public ?int $nivel_historial_id = null;

    public array $niveles = [];
    public array $grados = [];
    public array $grupos = [];
    public array $ciclos = [];

    protected $queryString = [
        'buscar' => ['except' => ''],
        'buscar_historial' => ['except' => ''],
    ];

    public function boot(): void
    {
        abort_unless(auth()->user()?->canAccess('documentos.crear'), 403);
    }

    public function mount(): void
    {
        $this->fecha_expedicion = now()->format('Y-m-d');
        $this->folio = $this->generarFolio();
        $this->ciclo_escolar_id = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->value('id');

        $this->cargarCatalogos();
    }

    public function updatedModo(): void
    {
        if ($this->modo === 'individual') {
            $this->seleccionados = [];
        } else {
            $this->limpiarAlumnoIndividual();
            $this->editando_id = null;
        }
    }

    public function updatedNivelId(): void
    {
        $this->grado_id = null;
        $this->grupo_id = null;
        $this->cargarGrupos();
    }

    public function updatedGradoId(): void
    {
        $this->grupo_id = null;
        $this->cargarGrupos();
    }

    public function updatedCicloEscolarId(): void
    {
        $this->grupo_id = null;
        $this->cargarGrupos();
    }


    public function updatedBuscarHistorial(): void
    {
        $this->resetPage('historial');
    }

    public function updatedEstadoHistorial(): void
    {
        $this->resetPage('historial');
    }

    public function updatedNivelHistorialId(): void
    {
        $this->resetPage('historial');
    }

    public function updatedMotivoTipo(string $value): void
    {
        $this->motivo_texto = $this->motivoPredeterminado($value);
        if ($this->selectedAlumno) {
            $this->regenerarContenido();
        }
    }

    public function updatedTutorId($value): void
    {
        if (! $this->selectedAlumnoId) {
            return;
        }

        if (! $value) {
            $this->tutor_id = null;
            $this->suscriptor_nombre = '';
            $this->suscriptor_parentesco = '';
            $this->suscriptor_calidad = '';
            $this->referencia_alumno = ($this->selectedAlumno['genero'] ?? 'H') === 'M' ? 'la alumna' : 'el alumno';
            $this->regenerarContenido();
            return;
        }

        $alumno = $this->alumnoConRelaciones($this->selectedAlumnoId);
        $tutor = $alumno->tutores->firstWhere('id', (int) $value)
            ?: (($alumno->tutor?->id === (int) $value) ? $alumno->tutor : null);

        if (! $tutor) {
            return;
        }

        $this->aplicarTutor($alumno, $tutor);
        $this->regenerarContenido();
    }

    public function cargarCatalogos(): void
    {
        $this->niveles = Nivel::query()->select('id', 'nombre', 'slug', 'cct')->orderBy('id')->get()->toArray();
        $this->grados = Grado::query()->select('id', 'nivel_id', 'nombre', 'orden')->orderBy('nivel_id')->orderBy('orden')->get()->toArray();
        $this->ciclos = CicloEscolar::query()->orderByDesc('inicio_anio')->get()->map(fn ($c) => [
            'id' => $c->id,
            'nombre' => $c->nombre,
            'es_actual' => (bool) $c->es_actual,
        ])->toArray();
        $this->cargarGrupos();
    }

    private function cargarGrupos(): void
    {
        $this->grupos = Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->select('id', 'nivel_id', 'grado_id', 'ciclo_escolar_id', 'asignacion_grupo_id')
            ->where('estado', 'activo')
            ->when($this->nivel_id, fn (Builder $q) => $q->where('nivel_id', $this->nivel_id))
            ->when($this->grado_id, fn (Builder $q) => $q->where('grado_id', $this->grado_id))
            ->when($this->ciclo_escolar_id, fn (Builder $q) => $q->where('ciclo_escolar_id', $this->ciclo_escolar_id))
            ->orderBy('nivel_id')->orderBy('grado_id')->orderBy('id')
            ->get()
            ->map(fn ($grupo) => [
                'id' => $grupo->id,
                'nivel_id' => $grupo->nivel_id,
                'grado_id' => $grupo->grado_id,
                'nombre' => $grupo->asignacionGrupo?->nombre ?? 'Sin grupo',
            ])->toArray();
    }

    public function seleccionarAlumno(int $id): void
    {
        $alumno = $this->alumnoConRelaciones($id);
        $this->editando_id = null;
        $this->folio = $this->generarFolio();
        $this->selectedAlumnoId = $alumno->id;
        $this->selectedAlumno = $this->mapAlumno($alumno);
        $this->cargarDatosRelacionados($alumno);
        $this->aplicarConfiguracionNivel($alumno);
        $this->aplicarDatosCartaDesdeAlumno($alumno);
    }

    private function cargarDatosRelacionados(Inscripcion $alumno): void
    {
        $tutores = $alumno->tutores
            ->filter(fn ($tutor) => (bool) ($tutor->pivot?->activo ?? true))
            ->map(fn ($tutor) => [
                'id' => $tutor->id,
                'nombre' => $tutor->nombre_completo,
                'parentesco' => $tutor->pivot?->parentesco ?: $tutor->parentesco,
                'principal' => (bool) ($tutor->pivot?->es_principal ?? false),
            ]);

        if ($alumno->tutor && ! $tutores->contains('id', $alumno->tutor->id)) {
            $tutores->push([
                'id' => $alumno->tutor->id,
                'nombre' => $alumno->tutor->nombre_completo,
                'parentesco' => $alumno->tutor->parentesco,
                'principal' => true,
            ]);
        }

        $this->tutores = $tutores->sortByDesc('principal')->values()->toArray();
        $this->docentes = $alumno->grupo?->docentes
            ?->filter(fn ($persona) => (bool) ($persona->pivot?->status ?? true))
            ->map(fn ($persona) => [
                'id' => $persona->id,
                'nombre' => $this->nombrePersona($persona),
            ])->values()->toArray() ?? [];
    }

    private function aplicarDatosCartaDesdeAlumno(Inscripcion $alumno): void
    {
        $tutorPrincipal = $alumno->tutores
            ->sortByDesc(fn ($t) => (bool) ($t->pivot?->es_principal ?? false))
            ->first(fn ($t) => (bool) ($t->pivot?->activo ?? true))
            ?: $alumno->tutor;

        if ($tutorPrincipal) {
            $this->tutor_id = $tutorPrincipal->id;
            $this->aplicarTutor($alumno, $tutorPrincipal);
        } else {
            $this->tutor_id = null;
            $this->suscriptor_nombre = '';
            $this->suscriptor_parentesco = '';
            $this->suscriptor_calidad = '';
        }

        $this->grado_texto = $alumno->grado?->nombre ?? '';
        $this->docente_nombre = $this->docentes[0]['nombre'] ?? '';

        $director = $alumno->nivel?->director;
        $this->destinatario_nombre = $director ? $this->nombreDirectorDestinatario($director) : '';
        $this->destinatario_cargo = $director?->cargo ?? '';
        $this->directora_nombre = $director ? trim(($director->titulo ? $director->titulo . ' ' : '') . $this->nombreDirector($director)) : '';

        $this->regenerarContenido();
    }

    private function aplicarTutor(Inscripcion $alumno, $tutor): void
    {
        $parentesco = $tutor->pivot?->parentesco ?: $tutor->parentesco ?: 'Tutor';
        $this->suscriptor_nombre = $tutor->nombre_completo;
        $this->suscriptor_parentesco = (string) $parentesco;
        $this->suscriptor_calidad = $this->calidadSuscriptor($alumno, (string) $parentesco, (string) ($tutor->genero ?? ''));
        $this->referencia_alumno = $this->referenciaAlumno($alumno, (string) $parentesco);
    }

    private function aplicarConfiguracionNivel(Inscripcion $alumno): void
    {
        $config = CartaCompromisoConfiguracion::query()->where('nivel_id', $alumno->nivel_id)->first();
        $defaults = $this->defaultsNivel($alumno->nivel?->slug ?? '');

        $this->membrete_tipo = $config?->membrete_tipo ?: $defaults['membrete_tipo'];
        $this->encabezado_linea_1 = $config?->encabezado_linea_1 ?: $defaults['encabezado_linea_1'];
        $this->encabezado_linea_2 = $config?->encabezado_linea_2 ?: $defaults['encabezado_linea_2'];
        $this->lugar = $config?->lugar_default ?: 'Cd. Altamirano, Gro.';
        $this->leyenda_anual = $config?->leyenda_anual ?: '2026, AÑO DE LA MUJER INDÍGENA';
        $this->destinatario_institucion = $config?->destinatario_institucion ?: 'Centro Universitario Moctezuma';
    }

    public function guardarConfiguracionNivel(): void
    {
        if (! $this->selectedAlumno) {
            $this->addError('selectedAlumno', 'Selecciona un alumno para identificar el nivel.');
            return;
        }

        $this->validate([
            'membrete_tipo' => ['required', 'in:seg,cum'],
            'encabezado_linea_1' => ['nullable', 'string', 'max:255'],
            'encabezado_linea_2' => ['nullable', 'string', 'max:255'],
            'lugar' => ['required', 'string', 'max:255'],
            'leyenda_anual' => ['nullable', 'string', 'max:255'],
            'destinatario_institucion' => ['required', 'string', 'max:255'],
        ]);

        $configuracion = CartaCompromisoConfiguracion::query()->firstOrNew([
            'nivel_id' => (int) $this->selectedAlumno['nivel_id'],
        ]);

        if (! $configuracion->exists) {
            $configuracion->created_by = auth()->id();
        }

        $configuracion->fill([
            'membrete_tipo' => $this->membrete_tipo,
            'encabezado_linea_1' => $this->encabezado_linea_1,
            'encabezado_linea_2' => $this->encabezado_linea_2,
            'lugar_default' => $this->lugar,
            'leyenda_anual' => $this->leyenda_anual,
            'destinatario_institucion' => $this->destinatario_institucion,
            'updated_by' => auth()->id(),
        ])->save();

        $this->dispatch('notificar', tipo: 'success', mensaje: 'Configuración de carta compromiso guardada para este nivel.');
    }

    public function regenerarContenido(): void
    {
        if (! $this->selectedAlumno) {
            return;
        }

        $genero = $this->selectedAlumno['genero'] ?? 'H';
        $apoyo = $genero === 'M' ? 'apoyarla' : 'apoyarlo';
        $nivelar = $genero === 'M' ? 'nivelarla' : 'nivelarlo';
        $alumnoNombre = $this->selectedAlumno['nombre_completo'];
        $motivo = trim($this->motivo_texto) ?: 'por una situación particular';
        $grado = trim($this->grado_texto) ?: 'grado correspondiente';
        $calidad = trim($this->suscriptor_calidad);

        $inicio = $this->inicioSuscriptorDesdeCalidad($this->suscriptor_calidad);

        $this->contenido_cuerpo = trim(sprintf(
            '%s %s%s %s, me dirijo a usted de la manera más atenta y respetuosa para solicitar la inscripción de %s, al %s grado, debido a que por las siguientes situaciones no pudo estudiar el grado anterior: %s, para lo cual me comprometo a %s en las actividades escolares que la maestra o maestro crea conveniente para %s con el resto del grupo.',
            $inicio,
            $this->suscriptor_nombre ?: '________________',
            $calidad !== '' ? ', ' . $calidad : ',',
            $alumnoNombre,
            $this->referencia_alumno ?: 'el alumno',
            $grado,
            $motivo,
            $apoyo,
            $nivelar,
        ));

        $this->dispatch('actualizar-editor-carta-compromiso', contenido: $this->contenido_cuerpo);
    }

    public function generar(): void
    {
        if ($this->modo === 'masivo') {
            $this->generarMasivas();
            return;
        }

        $this->generarIndividual();
    }

    private function generarIndividual(): void
    {
        $this->validarFormularioIndividual();
        $alumno = $this->alumnoConRelaciones((int) $this->selectedAlumnoId);

        $datos = $this->datosCarta($alumno, false);

        if ($this->editando_id) {
            $carta = CartaCompromisoModel::query()->findOrFail($this->editando_id);
            $this->invalidarDocumentoArchivado($carta);
            $carta->update(array_merge($datos, [
                'folio' => $this->folio,
                'updated_by' => auth()->id(),
                'estado_documento' => 'emitida',
                'cancelada_at' => null,
                'cancelada_por' => null,
            ]));
            $mensaje = 'Carta compromiso actualizada correctamente.';
        } else {
            $carta = CartaCompromisoModel::query()->create(array_merge($datos, [
                'folio' => $this->folio,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]));
            $mensaje = 'Carta compromiso generada correctamente.';
        }

        $this->editando_id = $carta->id;
        $this->dispatch('abrir-carta-compromiso', url: route('misrutas.cartas-compromiso.pdf', $carta));
        $this->dispatch('notificar', tipo: 'success', mensaje: $mensaje);
    }

    private function generarMasivas(): void
    {
        $ids = collect($this->seleccionados)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            $this->addError('seleccionados', 'Selecciona al menos un alumno.');
            $this->dispatch('cerrar-ventana-carta-vacia');
            return;
        }

        $this->validate([
            'fecha_expedicion' => ['required', 'date'],
            'motivo_tipo' => ['required', 'string', 'max:60'],
            'motivo_texto' => ['required', 'string', 'max:1000'],
        ]);

        $cartasIds = [];
        $alumnos = Inscripcion::query()
            ->whereIn('id', $ids)
            ->with($this->relacionesAlumno())
            ->get()
            ->sortBy(fn ($alumno) => $ids->search($alumno->id));

        foreach ($alumnos as $alumno) {
            $datos = $this->datosCartaMasiva($alumno);
            $carta = CartaCompromisoModel::query()->create(array_merge($datos, [
                'folio' => $this->generarFolio(),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]));
            $cartasIds[] = $carta->id;
        }

        if ($cartasIds === []) {
            $this->dispatch('cerrar-ventana-carta-vacia');
            $this->dispatch('notificar', tipo: 'error', mensaje: 'No fue posible generar cartas para la selección.');
            return;
        }

        $url = route('misrutas.cartas-compromiso.masivas.pdf', ['ids' => implode(',', $cartasIds)]);
        $this->dispatch('abrir-carta-compromiso', url: $url);
        $this->dispatch('notificar', tipo: 'success', mensaje: count($cartasIds) . ' carta(s) compromiso generada(s).');
    }

    private function validarFormularioIndividual(): void
    {
        $this->validate([
            'selectedAlumnoId' => ['required', 'exists:inscripciones,id'],
            'folio' => ['required', 'string', 'max:50', Rule::unique('cartas_compromiso', 'folio')->ignore($this->editando_id)],
            'fecha_expedicion' => ['required', 'date'],
            'lugar' => ['required', 'string', 'max:255'],
            'asunto' => ['required', 'string', 'max:255'],
            'membrete_tipo' => ['required', 'in:seg,cum'],
            'destinatario_nombre' => ['required', 'string', 'max:255'],
            'destinatario_cargo' => ['required', 'string', 'max:255'],
            'destinatario_institucion' => ['required', 'string', 'max:255'],
            'suscriptor_nombre' => ['required', 'string', 'max:255'],
            'grado_texto' => ['required', 'string', 'max:255'],
            'motivo_texto' => ['required', 'string', 'max:1000'],
            'contenido_cuerpo' => ['required', 'string', 'max:5000'],
        ], [
            'selectedAlumnoId.required' => 'Selecciona un alumno.',
            'suscriptor_nombre.required' => 'Selecciona o captura el responsable que suscribe la carta.',
        ]);
    }

    private function datosCarta(Inscripcion $alumno, bool $masivo): array
    {
        return [
            'inscripcion_id' => $alumno->id,
            'tutor_id' => $masivo ? null : $this->tutor_id,
            'nivel_id' => $alumno->nivel_id,
            'grado_id' => $alumno->grado_id,
            'grupo_id' => $alumno->grupo_id,
            'ciclo_escolar_id' => $alumno->ciclo_escolar_id,
            'documento_alumno_id' => null,
            'fecha_expedicion' => $this->fecha_expedicion,
            'lugar' => $this->lugar,
            'asunto' => $this->asunto,
            'leyenda_anual' => $this->leyenda_anual,
            'membrete_tipo' => $this->membrete_tipo,
            'encabezado_linea_1' => $this->encabezado_linea_1,
            'encabezado_linea_2' => $this->encabezado_linea_2,
            'destinatario_nombre' => $this->destinatario_nombre,
            'destinatario_cargo' => $this->destinatario_cargo,
            'destinatario_institucion' => $this->destinatario_institucion,
            'suscriptor_nombre' => $this->suscriptor_nombre,
            'suscriptor_parentesco' => $this->suscriptor_parentesco,
            'suscriptor_calidad' => $this->suscriptor_calidad,
            'referencia_alumno' => $this->referencia_alumno,
            'grado_texto' => $this->grado_texto,
            'motivo_tipo' => $this->motivo_tipo,
            'motivo_texto' => $this->motivo_texto,
            'contenido_cuerpo' => app(HtmlSanitizerService::class)->sanitize($this->contenido_cuerpo),
            'docente_nombre' => $this->docente_nombre,
            'directora_nombre' => $this->directora_nombre,
            'estado_documento' => 'emitida',
        ];
    }

    private function datosCartaMasiva(Inscripcion $alumno): array
    {
        $config = CartaCompromisoConfiguracion::query()->where('nivel_id', $alumno->nivel_id)->first();
        $defaults = $this->defaultsNivel($alumno->nivel?->slug ?? '');
        $tutor = $alumno->tutores
            ->sortByDesc(fn ($t) => (bool) ($t->pivot?->es_principal ?? false))
            ->first(fn ($t) => (bool) ($t->pivot?->activo ?? true))
            ?: $alumno->tutor;
        $parentesco = $tutor?->pivot?->parentesco ?: $tutor?->parentesco ?: 'Tutor';
        $suscriptor = $tutor?->nombre_completo ?: '________________';
        $calidad = $this->calidadSuscriptor($alumno, (string) $parentesco, (string) ($tutor?->genero ?? ''));
        $referencia = $this->referenciaAlumno($alumno, (string) $parentesco);
        $director = $alumno->nivel?->director;
        $docente = $alumno->grupo?->docentes?->first(fn ($p) => (bool) ($p->pivot?->status ?? true));
        $grado = $alumno->grado?->nombre ?? '';
        $genero = $alumno->genero;
        $apoyo = $genero === 'M' ? 'apoyarla' : 'apoyarlo';
        $nivelar = $genero === 'M' ? 'nivelarla' : 'nivelarlo';
        $inicio = $this->inicioSuscriptorDesdeCalidad($calidad);
        $contenido = sprintf(
            '%s %s%s %s, me dirijo a usted de la manera más atenta y respetuosa para solicitar la inscripción de %s, al %s grado, debido a que por las siguientes situaciones no pudo estudiar el grado anterior: %s, para lo cual me comprometo a %s en las actividades escolares que la maestra o maestro crea conveniente para %s con el resto del grupo.',
            $inicio,
            $suscriptor,
            $calidad !== '' ? ', ' . $calidad : ',',
            $this->nombreAlumno($alumno),
            $referencia,
            $grado,
            $this->motivo_texto,
            $apoyo,
            $nivelar,
        );

        return [
            'inscripcion_id' => $alumno->id,
            'tutor_id' => $tutor?->id,
            'nivel_id' => $alumno->nivel_id,
            'grado_id' => $alumno->grado_id,
            'grupo_id' => $alumno->grupo_id,
            'ciclo_escolar_id' => $alumno->ciclo_escolar_id,
            'documento_alumno_id' => null,
            'fecha_expedicion' => $this->fecha_expedicion,
            'lugar' => $config?->lugar_default ?: 'Cd. Altamirano, Gro.',
            'asunto' => 'CARTA COMPROMISO',
            'leyenda_anual' => $config?->leyenda_anual ?: $this->leyenda_anual,
            'membrete_tipo' => $config?->membrete_tipo ?: $defaults['membrete_tipo'],
            'encabezado_linea_1' => $config?->encabezado_linea_1 ?: $defaults['encabezado_linea_1'],
            'encabezado_linea_2' => $config?->encabezado_linea_2 ?: $defaults['encabezado_linea_2'],
            'destinatario_nombre' => $director ? $this->nombreDirectorDestinatario($director) : '',
            'destinatario_cargo' => $director?->cargo ?? '',
            'destinatario_institucion' => $config?->destinatario_institucion ?: 'Centro Universitario Moctezuma',
            'suscriptor_nombre' => $suscriptor,
            'suscriptor_parentesco' => (string) $parentesco,
            'suscriptor_calidad' => $calidad,
            'referencia_alumno' => $referencia,
            'grado_texto' => $grado,
            'motivo_tipo' => $this->motivo_tipo,
            'motivo_texto' => $this->motivo_texto,
            'contenido_cuerpo' => app(HtmlSanitizerService::class)->sanitize($contenido),
            'docente_nombre' => $docente ? $this->nombrePersona($docente) : '',
            'directora_nombre' => $director ? trim(($director->titulo ? $director->titulo . ' ' : '') . $this->nombreDirector($director)) : '',
            'estado_documento' => 'emitida',
        ];
    }

    public function seleccionarResultadosVisibles(): void
    {
        $ids = $this->consultaAlumnos()->limit(40)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->seleccionados = collect($this->seleccionados)->merge($ids)->unique()->values()->all();
    }

    public function limpiarSeleccionMasiva(): void
    {
        $this->seleccionados = [];
    }

    public function editarCarta(int $id): void
    {
        $carta = CartaCompromisoModel::query()->with('alumno')->findOrFail($id);
        $alumno = $this->alumnoConRelaciones($carta->inscripcion_id);

        $this->modo = 'individual';
        $this->editando_id = $carta->id;
        $this->selectedAlumnoId = $alumno->id;
        $this->selectedAlumno = $this->mapAlumno($alumno);
        $this->cargarDatosRelacionados($alumno);

        foreach ([
            'folio', 'lugar', 'asunto', 'leyenda_anual', 'membrete_tipo', 'encabezado_linea_1', 'encabezado_linea_2',
            'destinatario_nombre', 'destinatario_cargo', 'destinatario_institucion', 'suscriptor_nombre',
            'suscriptor_parentesco', 'suscriptor_calidad', 'referencia_alumno', 'grado_texto', 'motivo_tipo',
            'motivo_texto', 'contenido_cuerpo', 'docente_nombre', 'directora_nombre',
        ] as $campo) {
            $this->{$campo} = (string) ($carta->{$campo} ?? '');
        }

        $this->tutor_id = $carta->tutor_id;
        $this->fecha_expedicion = $carta->fecha_expedicion?->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->dispatch('desplazar-formulario-carta');
        $this->dispatch('actualizar-editor-carta-compromiso', contenido: $this->contenido_cuerpo);
    }

    public function duplicarCarta(int $id): void
    {
        $this->editarCarta($id);
        $this->editando_id = null;
        $this->folio = $this->generarFolio();
        $this->dispatch('notificar', tipo: 'info', mensaje: 'Se cargó una copia editable. Al guardar se creará una carta nueva.');
    }

    public function cancelarCarta(int $id): void
    {
        $carta = CartaCompromisoModel::query()->with('documentoAlumno')->findOrFail($id);
        if ($carta->estado_documento === 'cancelada') {
            return;
        }

        $carta->update([
            'estado_documento' => 'cancelada',
            'cancelada_at' => now(),
            'cancelada_por' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        if ($carta->documentoAlumno) {
            $carta->documentoAlumno->forceFill([
                'estado' => 'cancelada',
                'es_actual' => false,
            ])->save();
        }

        $this->dispatch('notificar', tipo: 'success', mensaje: 'Carta compromiso cancelada. El PDF archivado dejó de marcarse como vigente.');
    }

    public function abrirPdf(int $id): void
    {
        $carta = CartaCompromisoModel::query()->findOrFail($id);
        if ($carta->estado_documento === 'cancelada') {
            $this->dispatch('notificar', tipo: 'error', mensaje: 'La carta está cancelada y no puede emitirse.');
            return;
        }
        $this->dispatch('abrir-carta-compromiso', url: route('misrutas.cartas-compromiso.pdf', $carta));
    }

    public function nuevo(): void
    {
        $this->editando_id = null;
        $this->folio = $this->generarFolio();
        $this->limpiarAlumnoIndividual();
        $this->motivo_tipo = 'economicos';
        $this->motivo_texto = $this->motivoPredeterminado('economicos');
        $this->fecha_expedicion = now()->format('Y-m-d');
    }

    private function invalidarDocumentoArchivado(CartaCompromisoModel $carta): void
    {
        if (! $carta->documento_alumno_id) {
            return;
        }

        $documento = $carta->documentoAlumno;
        if ($documento) {
            $documento->forceFill(['estado' => 'reemplazado', 'es_actual' => false])->save();
        }
        $carta->documento_alumno_id = null;
    }

    private function limpiarAlumnoIndividual(): void
    {
        $this->selectedAlumnoId = null;
        $this->selectedAlumno = null;
        $this->tutores = [];
        $this->docentes = [];
        $this->tutor_id = null;
        $this->suscriptor_nombre = '';
        $this->suscriptor_parentesco = '';
        $this->suscriptor_calidad = '';
        $this->referencia_alumno = '';
        $this->grado_texto = '';
        $this->contenido_cuerpo = '';
        $this->docente_nombre = '';
        $this->directora_nombre = '';
    }

    private function alumnoConRelaciones(int $id): Inscripcion
    {
        return Inscripcion::query()->with($this->relacionesAlumno())->findOrFail($id);
    }

    private function relacionesAlumno(): array
    {
        return [
            'nivel.director',
            'grado:id,nombre,nivel_id,orden',
            'grupo.asignacionGrupo:id,nombre',
            'grupo.docentes',
            'cicloEscolar',
            'tutores',
            'tutor',
        ];
    }

    private function consultaAlumnos(): Builder
    {
        $termino = trim($this->buscar);

        return Inscripcion::query()
            ->with(['nivel:id,nombre', 'grado:id,nombre', 'grupo.asignacionGrupo:id,nombre'])
            ->where('activo', true)
            ->when($this->ciclo_escolar_id, fn (Builder $q) => $q->where('ciclo_escolar_id', $this->ciclo_escolar_id))
            ->when($this->nivel_id, fn (Builder $q) => $q->where('nivel_id', $this->nivel_id))
            ->when($this->grado_id, fn (Builder $q) => $q->where('grado_id', $this->grado_id))
            ->when($this->grupo_id, fn (Builder $q) => $q->where('grupo_id', $this->grupo_id))
            ->when($termino !== '', function (Builder $q) use ($termino) {
                $like = '%' . str_replace(' ', '%', $termino) . '%';
                $q->where(function (Builder $sub) use ($like) {
                    $sub->where('nombre', 'like', $like)
                        ->orWhere('apellido_paterno', 'like', $like)
                        ->orWhere('apellido_materno', 'like', $like)
                        ->orWhere('matricula', 'like', $like)
                        ->orWhere('curp', 'like', $like)
                        ->orWhereRaw("CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?", [$like]);
                });
            })
            ->orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombre');
    }

    private function consultaHistorial(): Builder
    {
        $termino = trim($this->buscar_historial);

        return CartaCompromisoModel::query()
            ->with(['alumno.nivel:id,nombre', 'alumno.grado:id,nombre', 'alumno.grupo.asignacionGrupo:id,nombre', 'usuarioCreador:id,name'])
            ->when($this->estado_historial !== 'todos', fn (Builder $q) => $q->where('estado_documento', $this->estado_historial))
            ->when($this->nivel_historial_id, fn (Builder $q) => $q->where('nivel_id', $this->nivel_historial_id))
            ->when($termino !== '', function (Builder $q) use ($termino) {
                $like = '%' . str_replace(' ', '%', $termino) . '%';
                $q->where(function (Builder $sub) use ($like) {
                    $sub->where('folio', 'like', $like)
                        ->orWhere('suscriptor_nombre', 'like', $like)
                        ->orWhereHas('alumno', function (Builder $alumno) use ($like) {
                            $alumno->where('matricula', 'like', $like)
                                ->orWhere('curp', 'like', $like)
                                ->orWhereRaw("CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?", [$like]);
                        });
                });
            })
            ->latest('fecha_expedicion')->latest('id');
    }

    private function mapAlumno(Inscripcion $alumno): array
    {
        return [
            'id' => $alumno->id,
            'nombre_completo' => $this->nombreAlumno($alumno),
            'matricula' => $alumno->matricula,
            'curp' => $alumno->curp,
            'genero' => $alumno->genero,
            'nivel_id' => $alumno->nivel_id,
            'nivel' => $alumno->nivel?->nombre ?? '',
            'grado' => $alumno->grado?->nombre ?? '',
            'grupo' => $alumno->grupo?->asignacionGrupo?->nombre ?? '',
            'ciclo' => $alumno->cicloEscolar?->nombre ?? '',
        ];
    }

    private function nombreAlumno(Inscripcion $alumno): string
    {
        return trim(collect([$alumno->nombre, $alumno->apellido_paterno, $alumno->apellido_materno])->filter()->join(' '));
    }

    private function nombrePersona($persona): string
    {
        return trim(collect([$persona->titulo, $persona->nombre, $persona->apellido_paterno, $persona->apellido_materno])->filter()->join(' '));
    }

    private function nombreDirector($director): string
    {
        return trim(collect([$director->nombre, $director->apellido_paterno, $director->apellido_materno])->filter()->join(' '));
    }

    private function nombreDirectorDestinatario($director): string
    {
        $titulo = trim((string) ($director->titulo ?? ''));
        $nombre = $this->nombreDirector($director);

        return trim('C. ' . ($titulo !== '' ? $titulo . ' ' : '') . $nombre);
    }

    private function calidadSuscriptor(Inscripcion $alumno, string $parentesco, string $generoTutor): string
    {
        $p = Str::lower(Str::ascii(trim($parentesco)));
        $sujeto = $this->sujetoAlumno($alumno);

        if (Str::contains($p, ['madre', 'mama'])) return 'mamá ' . $sujeto;
        if (Str::contains($p, ['padre', 'papa'])) return 'papá ' . $sujeto;
        if (Str::contains($p, 'abuela')) return 'abuela ' . $sujeto;
        if (Str::contains($p, 'abuelo')) return 'abuelo ' . $sujeto;
        if (Str::contains($p, 'tia')) return 'tía ' . $sujeto;
        if (Str::contains($p, 'tio')) return 'tío ' . $sujeto;

        return ($generoTutor === 'F' ? 'tutora ' : 'tutor ') . $sujeto;
    }

    private function sujetoAlumno(Inscripcion $alumno): string
    {
        $femenino = $alumno->genero === 'M';
        if ($alumno->nivel?->slug === 'preescolar') {
            return $femenino ? 'de la niña' : 'del niño';
        }
        return $femenino ? 'de la alumna' : 'del alumno';
    }


    private function inicioSuscriptorDesdeCalidad(string $calidad): string
    {
        $normalizada = Str::lower(Str::ascii(trim($calidad)));

        if (Str::startsWith($normalizada, ['mama', 'madre', 'abuela', 'tia', 'tutora'])) {
            return 'La que suscribe';
        }

        return 'El que suscribe';
    }

    private function referenciaAlumno(Inscripcion $alumno, string $parentesco): string
    {
        $p = Str::lower(Str::ascii(trim($parentesco)));
        $femenino = $alumno->genero === 'M';

        if (Str::contains($p, ['madre', 'mama', 'padre', 'papa'])) {
            return $femenino ? 'mi hija' : 'mi hijo';
        }

        return $femenino ? 'la alumna' : 'el alumno';
    }

    private function motivoPredeterminado(string $tipo): string
    {
        return match ($tipo) {
            'economicos' => 'por motivos económicos',
            'familiares' => 'por motivos familiares',
            'salud' => 'por motivos de salud',
            'cambio_residencia' => 'por cambio de residencia',
            'situacion_academica' => 'por una situación académica particular',
            'incorporacion_tardia' => 'por incorporación tardía al ciclo escolar',
            default => '',
        };
    }

    private function defaultsNivel(string $slug): array
    {
        return match ($slug) {
            'preescolar' => [
                'membrete_tipo' => 'seg',
                'encabezado_linea_1' => 'SUBSECRETARÍA DE EDUCACIÓN BÁSICA',
                'encabezado_linea_2' => 'DIRECCIÓN GENERAL DE EDUCACIÓN INICIAL Y PREESCOLAR',
            ],
            'primaria' => [
                'membrete_tipo' => 'seg',
                'encabezado_linea_1' => 'SECRETARÍA DE EDUCACIÓN GUERRERO',
                'encabezado_linea_2' => 'CENTRO UNIVERSITARIO MOCTEZUMA · PRIMARIA',
            ],
            'secundaria' => [
                'membrete_tipo' => 'seg',
                'encabezado_linea_1' => 'SECRETARÍA DE EDUCACIÓN GUERRERO',
                'encabezado_linea_2' => 'CENTRO UNIVERSITARIO MOCTEZUMA · SECUNDARIA',
            ],
            default => [
                'membrete_tipo' => 'cum',
                'encabezado_linea_1' => 'CENTRO UNIVERSITARIO MOCTEZUMA',
                'encabezado_linea_2' => 'BACHILLERATO',
            ],
        };
    }

    private function generarFolio(): string
    {
        $anio = now()->year;
        $ultimo = CartaCompromisoModel::query()
            ->where('folio', 'like', "CC-{$anio}-%")
            ->orderByDesc('id')
            ->value('folio');
        $consecutivo = 1;

        if ($ultimo && preg_match('/CC-' . $anio . '-(\d+)/', $ultimo, $m)) {
            $consecutivo = ((int) $m[1]) + 1;
        }

        return sprintf('CC-%d-%05d', $anio, $consecutivo);
    }

    public function render()
    {
        $resultados = $this->consultaAlumnos()->limit(40)->get();
        $historial = $this->consultaHistorial()->paginate(10, ['*'], 'historial');

        return view('livewire.documentacion.carta-compromiso', [
            'resultados' => $resultados,
            'historial' => $historial,
        ]);
    }
}
