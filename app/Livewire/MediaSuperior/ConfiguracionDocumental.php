<?php

namespace App\Livewire\MediaSuperior;

use App\Models\ConfiguracionMediaSuperior;
use App\Models\Director;
use App\Models\Escuela;
use App\Models\FirmanteMediaSuperior;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\cicloEscolar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class ConfiguracionDocumental extends Component
{
    use WithFileUploads;

    public string $nombre_plantel_oficial = '';
    public string $numero_acuerdo = 'SEG/0031/2021';
    public string $fecha_acuerdo = '';
    public string $modalidad = 'Escolarizada';
    public string $turno = 'Matutino';
    public string $calificacion_minima = '5';
    public string $calificacion_maxima = '10';
    public string $minima_aprobatoria = '6';
    public string $localidad_expedicion = '';
    public string $logo_seg_path = 'imagenes/logo-seg.png';
    public string $logo_plantel_path = 'imagenes/logo-letra.png';
    public string $texto_certificado = '';
    public string $leyenda_certificado = '';
    public bool $mostrar_materias_extra = true;
    public bool $mostrar_foto_historial = false;

    /** @var array<string, array<string, mixed>> */
    public array $firmantes = [];

    /** @var array<string, mixed> */
    public array $firmaUploads = [];

    /** @var array<string, mixed> */
    public array $selloUploads = [];

    /** @var array<string, bool> */
    public array $eliminarFirmas = [];

    /** @var array<string, bool> */
    public array $eliminarSellos = [];

    public function mount(): void
    {
        Gate::authorize('configurar-firmas-documentales');

        $nivel = $this->nivel;
        $escuela = Escuela::query()->first();
        $config = ConfiguracionMediaSuperior::query()->firstOrNew(['nivel_id' => $nivel->id]);

        $this->nombre_plantel_oficial = (string) ($config->nombre_plantel_oficial ?: $escuela?->nombre);
        $this->numero_acuerdo = (string) ($config->numero_acuerdo ?: 'SEG/0031/2021');
        $this->fecha_acuerdo = $config->fecha_acuerdo?->format('Y-m-d') ?: '';
        $this->modalidad = (string) ($config->modalidad ?: 'Escolarizada');
        $this->turno = (string) ($config->turno ?: 'Matutino');
        $this->calificacion_minima = (string) ($config->calificacion_minima ?? 5);
        $this->calificacion_maxima = (string) ($config->calificacion_maxima ?? 10);
        $this->minima_aprobatoria = (string) ($config->minima_aprobatoria ?? 6);
        $this->localidad_expedicion = (string) ($config->localidad_expedicion
            ?: collect([$escuela?->ciudad, $escuela?->estado])->filter()->implode(', '));
        $this->logo_seg_path = (string) ($config->logo_seg_path ?: 'imagenes/logo-seg.png');
        $this->logo_plantel_path = (string) ($config->logo_plantel_path ?: 'imagenes/logo-letra.png');
        $this->texto_certificado = (string) ($config->texto_certificado ?: $this->textoCertificadoPredeterminado());
        $this->leyenda_certificado = (string) ($config->leyenda_certificado ?: $this->leyendaCertificadoPredeterminada());
        $this->mostrar_materias_extra = (bool) ($config->mostrar_materias_extra ?? true);
        $this->mostrar_foto_historial = (bool) ($config->mostrar_foto_historial ?? false);

        $cicloActualId = cicloEscolar::query()->where('es_actual', true)->value('id')
            ?: cicloEscolar::query()->max('id');

        foreach ($this->roles() as $rol => $cargo) {
            $actual = FirmanteMediaSuperior::query()
                ->where('nivel_id', $nivel->id)
                ->where('rol', $rol)
                ->when($cicloActualId, fn ($query) => $query->vigentePara((int) $cicloActualId))
                ->where('activo', true)
                ->latest('id')
                ->first();

            $tipo = 'persona';
            if ($actual?->director_id) {
                $tipo = $rol === FirmanteMediaSuperior::ROL_JEFE_REGISTRO ? 'autoridad' : 'director';
            }

            $this->firmantes[$rol] = [
                'registro_id' => $actual?->id,
                'tipo' => $tipo,
                'id' => (string) ($actual?->director_id ?: $actual?->persona_id ?: ''),
                'cargo' => (string) ($actual?->cargo_impresion ?: $cargo),
                'ciclo_desde_id' => (string) ($actual?->ciclo_desde_id ?: ''),
                'ciclo_hasta_id' => (string) ($actual?->ciclo_hasta_id ?: ''),
                'firma_path' => $actual?->firma_path,
                'sello_path' => $actual?->sello_path,
                'archivos_version' => optional($actual?->archivos_actualizados_at ?: $actual?->updated_at)?->timestamp,
            ];

            $this->eliminarFirmas[$rol] = false;
            $this->eliminarSellos[$rol] = false;
        }
    }

    public function updatedFirmantes(mixed $value, string $key): void
    {
        $rol = Str::beforeLast($key, '.');
        if (! isset($this->firmantes[$rol])) {
            return;
        }

        if (Str::endsWith($key, '.tipo')) {
            $this->firmantes[$rol]['id'] = '';
            $this->resetErrorBag("firmantes.$rol.id");
        }

        if (
            Str::endsWith($key, '.tipo')
            || Str::endsWith($key, '.id')
            || Str::endsWith($key, '.ciclo_desde_id')
            || Str::endsWith($key, '.ciclo_hasta_id')
        ) {
            // La firma pertenece a una persona y vigencia concretas. Al cambiar
            // cualquiera de esos datos no se reutiliza visualmente el archivo anterior.
            $this->firmaUploads[$rol] = null;
            $this->selloUploads[$rol] = null;
            $this->eliminarFirmas[$rol] = false;
            $this->eliminarSellos[$rol] = false;
            $this->firmantes[$rol]['registro_id'] = null;
            $this->firmantes[$rol]['firma_path'] = null;
            $this->firmantes[$rol]['sello_path'] = null;
            $this->firmantes[$rol]['archivos_version'] = now()->timestamp;
        }
    }

    public function quitarArchivo(string $rol, string $tipo): void
    {
        Gate::authorize('configurar-firmas-documentales');
        abort_unless($this->rolConArchivos($rol) && in_array($tipo, ['firma', 'sello'], true), 404);

        if ($tipo === 'firma') {
            $this->firmaUploads[$rol] = null;
            $this->eliminarFirmas[$rol] = true;
        } else {
            $this->selloUploads[$rol] = null;
            $this->eliminarSellos[$rol] = true;
        }

        $this->resetErrorBag(($tipo === 'firma' ? 'firmaUploads.' : 'selloUploads.') . $rol);
    }

    public function restaurarArchivo(string $rol, string $tipo): void
    {
        Gate::authorize('configurar-firmas-documentales');
        abort_unless($this->rolConArchivos($rol) && in_array($tipo, ['firma', 'sello'], true), 404);

        if ($tipo === 'firma') {
            $this->eliminarFirmas[$rol] = false;
        } else {
            $this->eliminarSellos[$rol] = false;
        }
    }

    #[Computed]
    public function nivel(): Nivel
    {
        return Nivel::query()->where('slug', 'bachillerato')->orWhere('id', 4)->firstOrFail();
    }

    #[Computed]
    public function directores()
    {
        return Director::query()
            ->where('status', true)
            ->where(function ($query): void {
                $query->where('identificador', 'like', '%director%')
                    ->orWhere('identificador', 'like', '%rector%')
                    ->orWhere('cargo', 'like', '%director%')
                    ->orWhere('cargo', 'like', '%rector%');
            })
            ->orderBy('apellido_paterno')
            ->orderBy('nombre')
            ->get();
    }

    #[Computed]
    public function autoridades()
    {
        return Director::query()
            ->where('status', true)
            ->orderBy('cargo')
            ->orderBy('apellido_paterno')
            ->orderBy('nombre')
            ->get();
    }

    #[Computed]
    public function personas()
    {
        return Persona::query()->where('status', true)->orderBy('apellido_paterno')->orderBy('nombre')->get();
    }

    #[Computed]
    public function ciclos()
    {
        return cicloEscolar::query()->orderByDesc('inicio_anio')->get();
    }

    #[Computed]
    public function avance(): array
    {
        $campos = [
            filled($this->nombre_plantel_oficial),
            filled($this->numero_acuerdo),
            filled($this->fecha_acuerdo),
            filled($this->modalidad),
            filled($this->turno),
            filled($this->localidad_expedicion),
            filled($this->texto_certificado),
            filled($this->leyenda_certificado),
        ];
        $firmantesConfigurados = collect($this->firmantes)->filter(fn (array $firmante) => filled($firmante['id'] ?? null))->count();
        $completados = collect($campos)->filter()->count() + $firmantesConfigurados;
        $total = count($campos) + count($this->roles());

        return [
            'completados' => $completados,
            'total' => $total,
            'porcentaje' => $total > 0 ? (int) round(($completados / $total) * 100) : 0,
            'firmantes' => $firmantesConfigurados,
        ];
    }

    public function guardar(): void
    {
        Gate::authorize('configurar-firmas-documentales');

        $this->validate([
            'nombre_plantel_oficial' => ['nullable', 'string', 'max:255'],
            'numero_acuerdo' => ['nullable', 'string', 'max:120'],
            'fecha_acuerdo' => ['nullable', 'date'],
            'modalidad' => ['required', 'string', 'max:80'],
            'turno' => ['required', 'string', 'max:80'],
            'calificacion_minima' => ['required', 'numeric', 'min:0', 'max:10', 'lt:calificacion_maxima'],
            'calificacion_maxima' => ['required', 'numeric', 'min:0', 'max:10', 'gt:calificacion_minima'],
            'minima_aprobatoria' => ['required', 'numeric', 'gte:calificacion_minima', 'lte:calificacion_maxima'],
            'localidad_expedicion' => ['nullable', 'string', 'max:255'],
            'logo_seg_path' => ['nullable', 'string', 'max:255'],
            'logo_plantel_path' => ['nullable', 'string', 'max:255'],
            'texto_certificado' => ['required', 'string', 'max:4000'],
            'leyenda_certificado' => ['required', 'string', 'max:1000'],
            'mostrar_materias_extra' => ['boolean'],
            'mostrar_foto_historial' => ['boolean'],
            'firmantes.*.tipo' => ['required', 'in:director,persona,autoridad'],
            'firmantes.*.id' => ['nullable', 'integer'],
            'firmantes.*.cargo' => ['required', 'string', 'max:255'],
            'firmantes.*.ciclo_desde_id' => ['nullable', 'integer', 'exists:ciclo_escolares,id'],
            'firmantes.*.ciclo_hasta_id' => ['nullable', 'integer', 'exists:ciclo_escolares,id'],
            'firmaUploads.*' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'selloUploads.*' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ], [
            'firmaUploads.*.max' => 'La firma no puede superar 2 MB.',
            'selloUploads.*.max' => 'El sello no puede superar 2 MB.',
            'firmaUploads.*.mimes' => 'La firma debe ser PNG, JPG, JPEG o WebP.',
            'selloUploads.*.mimes' => 'El sello debe ser PNG, JPG, JPEG o WebP.',
        ]);

        $this->validarFirmantesSeleccionados();

        $archivosNuevos = [];
        $archivosParaEliminar = [];

        try {
            DB::transaction(function () use (&$archivosNuevos, &$archivosParaEliminar): void {
                ConfiguracionMediaSuperior::query()->updateOrCreate(
                    ['nivel_id' => $this->nivel->id],
                    [
                        'nombre_plantel_oficial' => $this->vacioANull($this->nombre_plantel_oficial),
                        'numero_acuerdo' => $this->vacioANull($this->numero_acuerdo),
                        'fecha_acuerdo' => $this->vacioANull($this->fecha_acuerdo),
                        'modalidad' => $this->modalidad,
                        'turno' => $this->turno,
                        'calificacion_minima' => (float) $this->calificacion_minima,
                        'calificacion_maxima' => (float) $this->calificacion_maxima,
                        'minima_aprobatoria' => (float) $this->minima_aprobatoria,
                        'localidad_expedicion' => $this->vacioANull($this->localidad_expedicion),
                        'logo_seg_path' => $this->vacioANull($this->logo_seg_path),
                        'logo_plantel_path' => $this->vacioANull($this->logo_plantel_path),
                        'texto_certificado' => trim($this->texto_certificado),
                        'leyenda_certificado' => trim($this->leyenda_certificado),
                        'mostrar_materias_extra' => $this->mostrar_materias_extra,
                        'mostrar_foto_historial' => $this->mostrar_foto_historial,
                    ],
                );

                foreach ($this->roles() as $rol => $cargoPredeterminado) {
                    $datos = $this->firmantes[$rol] ?? [];
                    $id = filled($datos['id'] ?? null) ? (int) $datos['id'] : null;

                    if (! $id) {
                        continue;
                    }

                    $cicloDesde = filled($datos['ciclo_desde_id'] ?? null) ? (int) $datos['ciclo_desde_id'] : null;
                    $cicloHasta = filled($datos['ciclo_hasta_id'] ?? null) ? (int) $datos['ciclo_hasta_id'] : null;
                    $tipo = (string) ($datos['tipo'] ?? 'persona');

                    $target = FirmanteMediaSuperior::query()
                        ->where('nivel_id', $this->nivel->id)
                        ->where('rol', $rol)
                        ->where('ciclo_desde_id', $cicloDesde)
                        ->where('ciclo_hasta_id', $cicloHasta)
                        ->first();

                    $directorId = in_array($tipo, ['director', 'autoridad'], true) ? $id : null;
                    $personaId = $tipo === 'persona' ? $id : null;
                    $cambioPersona = $target && (
                        (int) ($target->director_id ?? 0) !== (int) ($directorId ?? 0)
                        || (int) ($target->persona_id ?? 0) !== (int) ($personaId ?? 0)
                    );

                    $firmaPath = $target?->firma_path;
                    $selloPath = $target?->sello_path;
                    $archivosActualizados = false;

                    if ($this->rolConArchivos($rol)) {
                        $firmaPath = $this->resolverArchivoFirmante(
                            $rol,
                            'firma',
                            $target?->firma_path,
                            $cambioPersona,
                            $cicloDesde,
                            $cicloHasta,
                            $archivosNuevos,
                            $archivosParaEliminar,
                        );
                        $selloPath = $this->resolverArchivoFirmante(
                            $rol,
                            'sello',
                            $target?->sello_path,
                            $cambioPersona,
                            $cicloDesde,
                            $cicloHasta,
                            $archivosNuevos,
                            $archivosParaEliminar,
                        );
                        $archivosActualizados = $firmaPath !== $target?->firma_path || $selloPath !== $target?->sello_path;
                    } else {
                        $firmaPath = null;
                        $selloPath = null;
                    }

                    $registro = FirmanteMediaSuperior::query()->updateOrCreate(
                        [
                            'nivel_id' => $this->nivel->id,
                            'rol' => $rol,
                            'ciclo_desde_id' => $cicloDesde,
                            'ciclo_hasta_id' => $cicloHasta,
                        ],
                        [
                            'director_id' => $directorId,
                            'persona_id' => $personaId,
                            'cargo_impresion' => $datos['cargo'] ?: $cargoPredeterminado,
                            'firma_path' => $firmaPath,
                            'sello_path' => $selloPath,
                            'archivos_actualizados_por' => $archivosActualizados ? Auth::id() : $target?->archivos_actualizados_por,
                            'archivos_actualizados_at' => $archivosActualizados ? now() : $target?->archivos_actualizados_at,
                            'activo' => true,
                        ],
                    );

                    $this->firmantes[$rol]['registro_id'] = $registro->id;
                    $this->firmantes[$rol]['firma_path'] = $registro->firma_path;
                    $this->firmantes[$rol]['sello_path'] = $registro->sello_path;
                    $this->firmantes[$rol]['archivos_version'] = optional($registro->archivos_actualizados_at ?: $registro->updated_at)?->timestamp;
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete(array_values(array_unique($archivosNuevos)));
            throw $exception;
        }

        $archivosParaEliminar = array_values(array_diff(
            array_unique(array_filter($archivosParaEliminar)),
            array_unique(array_filter($archivosNuevos)),
        ));
        Storage::disk('local')->delete($archivosParaEliminar);

        $this->firmaUploads = [];
        $this->selloUploads = [];
        foreach (array_keys($this->roles()) as $rol) {
            $this->eliminarFirmas[$rol] = false;
            $this->eliminarSellos[$rol] = false;
        }

        unset($this->avance);
        $this->dispatch('swal', icon: 'success', title: 'Configuración guardada', text: 'Los firmantes, vigencias y archivos privados se actualizaron correctamente.');
    }

    public function render()
    {
        return view('livewire.media-superior.configuracion-documental', [
            'roles' => $this->roles(),
        ]);
    }

    /** @return array<string, string> */
    private function roles(): array
    {
        return [
            FirmanteMediaSuperior::ROL_DIRECTOR => 'DIRECTOR(A) DEL PLANTEL',
            FirmanteMediaSuperior::ROL_CONTROL_ESCOLAR => 'RESPONSABLE DE CONTROL ESCOLAR',
            FirmanteMediaSuperior::ROL_JEFE_REGISTRO => 'JEFE DEL DEPARTAMENTO DE REGISTRO Y CERTIFICACIÓN',
        ];
    }

    private function rolConArchivos(string $rol): bool
    {
        return in_array($rol, [
            FirmanteMediaSuperior::ROL_DIRECTOR,
            FirmanteMediaSuperior::ROL_JEFE_REGISTRO,
        ], true);
    }

    private function resolverArchivoFirmante(
        string $rol,
        string $tipo,
        ?string $actual,
        bool $cambioPersona,
        ?int $cicloDesde,
        ?int $cicloHasta,
        array &$archivosNuevos,
        array &$archivosParaEliminar,
    ): ?string {
        $upload = $tipo === 'firma'
            ? ($this->firmaUploads[$rol] ?? null)
            : ($this->selloUploads[$rol] ?? null);
        $eliminar = $tipo === 'firma'
            ? (bool) ($this->eliminarFirmas[$rol] ?? false)
            : (bool) ($this->eliminarSellos[$rol] ?? false);

        if ($upload) {
            $contexto = ($cicloDesde ?: 'inicio') . '-' . ($cicloHasta ?: 'abierto');
            $directorio = "firmas-documentales/nivel-{$this->nivel->id}/{$rol}/{$contexto}/{$tipo}";
            $extension = strtolower((string) ($upload->getClientOriginalExtension() ?: 'png'));
            $extension = in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true) ? $extension : 'png';
            $ruta = $upload->storeAs($directorio, Str::uuid() . '.' . $extension, 'local');

            if (! $ruta) {
                throw ValidationException::withMessages([
                    ($tipo === 'firma' ? 'firmaUploads.' : 'selloUploads.') . $rol => 'No fue posible guardar el archivo privado.',
                ]);
            }

            $archivosNuevos[] = $ruta;
            if ($actual && $actual !== $ruta) {
                $archivosParaEliminar[] = $actual;
            }

            return $ruta;
        }

        if ($eliminar || $cambioPersona) {
            if ($actual) {
                $archivosParaEliminar[] = $actual;
            }

            return null;
        }

        return $actual;
    }

    private function validarFirmantesSeleccionados(): void
    {
        $errores = [];
        $ciclos = cicloEscolar::query()->get(['id', 'inicio_anio', 'fin_anio'])->keyBy('id');

        foreach ($this->roles() as $rol => $cargo) {
            $datos = $this->firmantes[$rol] ?? [];
            $id = filled($datos['id'] ?? null) ? (int) $datos['id'] : null;
            $tipo = (string) ($datos['tipo'] ?? 'persona');

            if (! $id && $this->rolConArchivos($rol) && (($this->firmaUploads[$rol] ?? null) || ($this->selloUploads[$rol] ?? null))) {
                $errores["firmantes.$rol.id"] = "Selecciona primero a la persona que será firmante de $cargo.";
            }

            if ($id) {
                $existe = in_array($tipo, ['director', 'autoridad'], true)
                    ? Director::query()->whereKey($id)->where('status', true)->exists()
                    : Persona::query()->whereKey($id)->where('status', true)->exists();

                if (! $existe) {
                    $errores["firmantes.$rol.id"] = "El firmante seleccionado para $cargo no existe o está inactivo.";
                }
            }

            $desdeId = filled($datos['ciclo_desde_id'] ?? null) ? (int) $datos['ciclo_desde_id'] : null;
            $hastaId = filled($datos['ciclo_hasta_id'] ?? null) ? (int) $datos['ciclo_hasta_id'] : null;

            if ($desdeId && $hastaId) {
                $desde = $ciclos->get($desdeId);
                $hasta = $ciclos->get($hastaId);

                if ($desde && $hasta && (int) $desde->inicio_anio > (int) $hasta->inicio_anio) {
                    $errores["firmantes.$rol.ciclo_hasta_id"] = "La vigencia final de $cargo no puede ser anterior a la vigencia inicial.";
                }
            }
        }

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }

    private function textoCertificadoPredeterminado(): string
    {
        return <<<'TEXT'
CERTIFICA QUE: {NOMBRE}
CON CLAVE ÚNICA DE REGISTRO DE POBLACIÓN (CURP) {CURP}
CURSÓ Y ACREDITÓ {ACREDITACION} EL BACHILLERATO GENERAL
CON RECONOCIMIENTO DE VALIDEZ OFICIAL DE LA SECRETARÍA DE EDUCACIÓN GUERRERO, SEGÚN ACUERDO: {ACUERDO}, DE FECHA {FECHA_ACUERDO} Y CLAVE DE CENTRO DE TRABAJO {CCT}.
TEXT;
    }

    private function leyendaCertificadoPredeterminada(): string
    {
        return 'ESTE CERTIFICADO REQUIERE DE TRÁMITES ADICIONALES DE LEGALIZACIÓN, NO ES VÁLIDO SI PRESENTA BORRADURAS O ENMENDADURAS.';
    }

    private function vacioANull(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }
}
