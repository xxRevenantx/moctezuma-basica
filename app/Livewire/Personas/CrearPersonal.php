<?php

namespace App\Livewire\Personas;

use App\Models\Persona;
use App\Services\CurpService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class CrearPersonal extends Component
{
    use WithFileUploads;

    // =========================
    // Campos
    // =========================
    public ?string $titulo = null;
    public ?string $nombre = null;
    public ?string $apellido_paterno = null;
    public ?string $apellido_materno = null;

    public $foto = null;

    public ?string $curp = null;
    public ?string $rfc = null;

    public ?string $correo = null;
    public ?string $telefono_movil = null;
    public ?string $telefono_fijo = null;

    public ?string $fecha_nacimiento = null;
    public ?string $genero = null;

    public ?string $grado_estudios = null;
    public ?string $especialidad = null;

    public bool $status = true;

    // Dirección
    public ?string $calle = null;
    public ?string $numero_exterior = null;
    public ?string $numero_interior = null;
    public ?string $colonia = null;
    public ?string $municipio = null;
    public ?string $estado = null;
    public ?string $codigo_postal = null;

    // =========================
    // CURP UI/estado
    // =========================
    public ?string $curpError = null;
    public ?string $ultimaCurpConsultada = null;
    public array $datosCurp = [];

    protected function rules(): array
    {
        return [
            'titulo' => 'required|string|max:50',
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',

            'foto' => 'nullable|image|max:2048',

            'curp' => ['required', 'string', 'size:18', Rule::unique('personas', 'curp')],
            'rfc' => ['nullable', 'string', 'min:10', 'max:13', Rule::unique('personas', 'rfc')],

            'correo' => ['nullable', 'email', 'max:150', Rule::unique('personas', 'correo')],
            'telefono_movil' => 'nullable|string|size:10',
            'telefono_fijo' => 'nullable|string|size:10',

            'fecha_nacimiento' => 'required|date',
            'genero' => 'required|in:H,M',

            'grado_estudios' => 'nullable|string|max:255',
            'especialidad' => 'nullable|string|max:255',

            'calle' => 'nullable|string|max:255',
            'numero_exterior' => 'nullable|string|max:20',
            'numero_interior' => 'nullable|string|max:20',
            'colonia' => 'nullable|string|max:255',
            'municipio' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:255',
            'codigo_postal' => 'nullable|string|max:10',
        ];
    }

    protected function messages(): array
    {
        return [
            'titulo.required' => 'El título es obligatorio.',
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',

            'curp.required' => 'La CURP es obligatoria.',
            'curp.size' => 'La CURP debe tener 18 caracteres.',
            'curp.unique' => 'La CURP ya está registrada.',

            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'genero.required' => 'El género es obligatorio.',
            'genero.in' => 'El género seleccionado no es válido.',

            'rfc.unique' => 'El RFC ya está registrado.',
            'rfc.min' => 'El RFC debe tener al menos 10 caracteres.',
            'rfc.max' => 'El RFC no debe superar 13 caracteres.',

            'correo.unique' => 'El correo ya está registrado.',
            'correo.email' => 'El correo no es válido.',

            'foto.image' => 'La foto debe ser una imagen válida.',
            'foto.max' => 'La foto no debe superar los 2MB.',

            'telefono_movil.size' => 'El teléfono móvil debe tener 10 dígitos.',
            'telefono_fijo.size' => 'El teléfono fijo debe tener 10 dígitos.',
        ];
    }

    // =========================
    // Helpers (Title Case español)
    // =========================
    private function titleCaseNombre(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        // Normaliza espacios múltiples
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        // RENAPO suele mandar MAYÚSCULAS. Convertimos a Title Case multibyte.
        $value = mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

        // Partículas comunes en minúscula (salvo inicio de cadena)
        $lowerWords = [
            'De', 'Del', 'La', 'Las', 'Los',
            'Y', 'E',
            'San', 'Santa',
            'Van', 'Von',
        ];

        foreach ($lowerWords as $w) {
            // en medio: " Carlos Del Río " => "Carlos del Río"
            $value = preg_replace('/\b' . preg_quote($w, '/') . '\b/u', mb_strtolower($w, 'UTF-8'), $value) ?? $value;
        }

        // Si empieza con esas partículas, las volvemos a Title (ej. "Del Río")
        // (normalmente nombres no inician así, pero por si acaso)
        $value = preg_replace_callback('/^(de|del|la|las|los|y|e|san|santa|van|von)\b/iu', function ($m) {
            return mb_convert_case(mb_strtolower($m[0], 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }, $value) ?? $value;

        return $value;
    }

    // =========================
    // CURP live
    // =========================
    public function updatedCurp($value): void
    {
        $curp = strtoupper(trim((string) $value));
        $this->curp = $curp;

        $this->curpError = null;

        // Mientras escribe o si lo borra: limpia autollenado
        if (strlen($curp) < 18) {
            $this->resetAutollenadoRenapo();
            $this->ultimaCurpConsultada = null;
            return;
        }

        // Ya está completa: si es la misma que ya consultamos, no repetimos
        if ($this->ultimaCurpConsultada === $curp) {
            return;
        }

        $this->consultarCurp();
    }

    private function resetAutollenadoRenapo(): void
    {
        $this->reset([
            'nombre',
            'apellido_paterno',
            'apellido_materno',
            'fecha_nacimiento',
            'genero',
            'rfc',
            'datosCurp',
        ]);
    }

    public function consultarCurp(): void
    {
        if (! $this->curp || strlen($this->curp) !== 18) {
            return;
        }

        $this->datosCurp = [];
        $this->curpError = null;

        /** @var CurpService $servicio */
        $servicio = app(CurpService::class);

        $data = $servicio->obtenerDatosPorCurp($this->curp);
        $this->datosCurp = is_array($data) ? $data : [];

        if (($data['error'] ?? false) === true) {
            $this->resetAutollenadoRenapo();

            $this->curpError = $data['message'] ?? 'No se pudo consultar el CURP';

            $this->dispatch('swal', [
                'title' => $this->curpError,
                'text'  => $data['detail'] ?? null,
                'icon'  => 'error',
                'position' => 'top-end',
            ]);

            return;
        }

        $info = data_get($data, 'response.Solicitante', []);

        if (empty($info)) {
            $this->resetAutollenadoRenapo();

            $this->curpError = 'Este CURP no se encuentra en RENAPO.';

            $this->dispatch('swal', [
                'title' => $this->curpError,
                'icon'  => 'warning',
                'position' => 'top-end',
            ]);

            return;
        }

        // ✅ Renapo manda mayúsculas -> Title Case bonito
        $this->nombre = $this->titleCaseNombre($info['Nombres'] ?? '');
        $this->apellido_paterno = $this->titleCaseNombre($info['ApellidoPaterno'] ?? '');
        $this->apellido_materno = $this->titleCaseNombre($info['ApellidoMaterno'] ?? '');

        $fecha = $info['FechaNacimiento'] ?? null;
        $this->fecha_nacimiento = $fecha ? date('Y-m-d', strtotime($fecha)) : null;

        $sexo = $info['ClaveSexo'] ?? null;
        $this->genero = in_array($sexo, ['H', 'M'], true) ? $sexo : null;

        // RFC base 10 (el usuario puede completar homoclave si quiere)
        $this->rfc = strtoupper(substr($this->curp, 0, 10));

        // Marca como ya consultada
        $this->ultimaCurpConsultada = $this->curp;
    }

    // =========================
    // Guardar
    // =========================
    public function crearPersonal(): void
    {
        $this->validate();

        $fotoPath = null;
        if ($this->foto) {
            $path = $this->foto->store('personal', 'public');
            $fotoPath = basename($path);
        }

        Persona::create([
            'titulo' => $this->titulo,
            'nombre' => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'foto' => $fotoPath,
            'curp' => strtoupper(trim((string) $this->curp)),
            'rfc' => $this->rfc ? strtoupper(trim((string) $this->rfc)) : null,
            'correo' => $this->correo,
            'telefono_movil' => $this->telefono_movil,
            'telefono_fijo' => $this->telefono_fijo,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'genero' => $this->genero,
            'grado_estudios' => $this->grado_estudios,
            'especialidad' => $this->especialidad,
            'status' => $this->status,
            'calle' => $this->calle,
            'numero_exterior' => $this->numero_exterior,
            'numero_interior' => $this->numero_interior,
            'colonia' => $this->colonia,
            'municipio' => $this->municipio,
            'estado' => $this->estado,
            'codigo_postal' => $this->codigo_postal,
        ]);

        $this->dispatch('swal', [
            'title' => 'Personal creado exitosamente.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        // Reset del form, dejando status en true
        $this->reset();
        $this->status = true;

        $this->dispatch('refreshPersonal');
    }

    public function render()
    {
        return view('livewire.personas.crear-personal');
    }
}
