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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ConfiguracionDocumental extends Component
{
    public string $nombre_plantel_oficial = '';
    public string $numero_acuerdo = 'SEG/0031/2021';
    public string $modalidad = 'Escolarizada';
    public string $turno = 'Matutino';
    public string $localidad_expedicion = '';
    public string $logo_seg_path = 'imagenes/logo-seg.png';
    public string $logo_plantel_path = 'imagenes/logo-letra.png';
    public bool $mostrar_materias_extra = true;

    /** @var array<string, array<string, mixed>> */
    public array $firmantes = [];

    public function mount(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $nivel = $this->nivel;
        $escuela = Escuela::query()->first();
        $config = ConfiguracionMediaSuperior::query()->firstOrNew(['nivel_id' => $nivel->id]);

        $this->nombre_plantel_oficial = (string) ($config->nombre_plantel_oficial ?: $escuela?->nombre);
        $this->numero_acuerdo = (string) ($config->numero_acuerdo ?: 'SEG/0031/2021');
        $this->modalidad = (string) ($config->modalidad ?: 'Escolarizada');
        $this->turno = (string) ($config->turno ?: 'Matutino');
        $this->localidad_expedicion = (string) ($config->localidad_expedicion
            ?: collect([$escuela?->ciudad, $escuela?->estado])->filter()->implode(', '));
        $this->logo_seg_path = (string) ($config->logo_seg_path ?: 'imagenes/logo-seg.png');
        $this->logo_plantel_path = (string) ($config->logo_plantel_path ?: 'imagenes/logo-letra.png');
        $this->mostrar_materias_extra = (bool) ($config->mostrar_materias_extra ?? true);

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

            $this->firmantes[$rol] = [
                'tipo' => $actual?->director_id ? 'director' : 'persona',
                'id' => (string) ($actual?->director_id ?: $actual?->persona_id ?: ''),
                'cargo' => (string) ($actual?->cargo_impresion ?: $cargo),
                'ciclo_desde_id' => (string) ($actual?->ciclo_desde_id ?: ''),
                'ciclo_hasta_id' => (string) ($actual?->ciclo_hasta_id ?: ''),
            ];
        }
    }

    public function updatedFirmantes(mixed $value, string $key): void
    {
        if (! Str::endsWith($key, '.tipo')) {
            return;
        }

        $rol = Str::beforeLast($key, '.tipo');
        if (isset($this->firmantes[$rol])) {
            $this->firmantes[$rol]['id'] = '';
            $this->resetErrorBag("firmantes.$rol.id");
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
        return Director::query()->where('status', true)->orderBy('apellido_paterno')->orderBy('nombre')->get();
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

    public function guardar(): void
    {
        $this->validate([
            'nombre_plantel_oficial' => ['nullable', 'string', 'max:255'],
            'numero_acuerdo' => ['nullable', 'string', 'max:120'],
            'modalidad' => ['required', 'string', 'max:80'],
            'turno' => ['required', 'string', 'max:80'],
            'localidad_expedicion' => ['nullable', 'string', 'max:255'],
            'logo_seg_path' => ['nullable', 'string', 'max:255'],
            'logo_plantel_path' => ['nullable', 'string', 'max:255'],
            'mostrar_materias_extra' => ['boolean'],
            'firmantes.*.tipo' => ['required', 'in:director,persona'],
            'firmantes.*.id' => ['nullable', 'integer'],
            'firmantes.*.cargo' => ['required', 'string', 'max:255'],
            'firmantes.*.ciclo_desde_id' => ['nullable', 'integer', 'exists:ciclo_escolares,id'],
            'firmantes.*.ciclo_hasta_id' => ['nullable', 'integer', 'exists:ciclo_escolares,id'],
        ]);

        $this->validarFirmantesSeleccionados();

        DB::transaction(function (): void {
            ConfiguracionMediaSuperior::query()->updateOrCreate(
                ['nivel_id' => $this->nivel->id],
                [
                    'nombre_plantel_oficial' => $this->vacioANull($this->nombre_plantel_oficial),
                    'numero_acuerdo' => $this->vacioANull($this->numero_acuerdo),
                    'modalidad' => $this->modalidad,
                    'turno' => $this->turno,
                    'localidad_expedicion' => $this->vacioANull($this->localidad_expedicion),
                    'logo_seg_path' => $this->vacioANull($this->logo_seg_path),
                    'logo_plantel_path' => $this->vacioANull($this->logo_plantel_path),
                    'mostrar_materias_extra' => $this->mostrar_materias_extra,
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

                // Se conserva cualquier vigencia anterior. Si se vuelve a guardar la misma
                // ventana de ciclos, únicamente se actualiza ese registro.
                FirmanteMediaSuperior::query()->updateOrCreate(
                    [
                        'nivel_id' => $this->nivel->id,
                        'rol' => $rol,
                        'ciclo_desde_id' => $cicloDesde,
                        'ciclo_hasta_id' => $cicloHasta,
                    ],
                    [
                        'director_id' => ($datos['tipo'] ?? 'persona') === 'director' ? $id : null,
                        'persona_id' => ($datos['tipo'] ?? 'persona') === 'persona' ? $id : null,
                        'cargo_impresion' => $datos['cargo'] ?: $cargoPredeterminado,
                        'activo' => true,
                    ],
                );
            }
        });

        $this->dispatch('swal', icon: 'success', title: 'Configuración guardada', text: 'Los documentos oficiales usarán estos datos.');
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

    private function validarFirmantesSeleccionados(): void
    {
        $errores = [];
        $ciclos = cicloEscolar::query()->get(['id', 'inicio_anio', 'fin_anio'])->keyBy('id');

        foreach ($this->roles() as $rol => $cargo) {
            $datos = $this->firmantes[$rol] ?? [];
            $id = filled($datos['id'] ?? null) ? (int) $datos['id'] : null;
            $tipo = (string) ($datos['tipo'] ?? 'persona');

            if ($id) {
                $existe = $tipo === 'director'
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

    private function vacioANull(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }
}
