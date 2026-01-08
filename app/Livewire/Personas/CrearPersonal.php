<?php

namespace App\Livewire\Personas;

use App\Models\Persona;
use App\Services\CurpService;
use App\Services\CurpPdfParser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\AzureDocumentIntelligence;


class CrearPersonal extends Component
{
    use WithFileUploads;

    // =========================
    // Campos
    // =========================
    public ?string $nombre = null;
    public ?string $apellido_paterno = null;
    public ?string $apellido_materno = null;

    public $foto = null;

    public ?string $curp = null;
    public ?string $rfc = null;

    public ?string $fecha_nacimiento = null;
    public ?string $genero = null;

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
    // CURP UI/estado (RENAPO)
    // =========================
    public ?string $curpError = null;
    public ?string $ultimaCurpConsultada = null;
    public array $datosCurp = [];

    // =========================
    // PDF para autollenado (SOLO CURP)
    // =========================
    public $pdf_curp = null;

    public ?string $autollenadoError = null;
    public bool $autollenar_forzar = false;

    protected function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',

            'foto' => 'nullable|image|max:2048',

            'curp' => ['required', 'string', 'size:18', Rule::unique('personas', 'curp')],
            'rfc' => ['nullable', 'string', 'min:12', 'max:13', Rule::unique('personas', 'rfc')],

            'fecha_nacimiento' => 'required|date',
            'genero' => 'required|in:H,M',

            'calle' => 'nullable|string|max:255',
            'numero_exterior' => 'nullable|string|max:20',
            'numero_interior' => 'nullable|string|max:20',
            'colonia' => 'nullable|string|max:255',
            'municipio' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:255',
            'codigo_postal' => 'nullable|string|max:10',

            // PDF CURP (solo se valida cuando se usa el botón de autollenado)
            'pdf_curp' => 'nullable|file|mimes:pdf|max:51200',
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',

            'curp.required' => 'La CURP es obligatoria.',
            'curp.size' => 'La CURP debe tener 18 caracteres.',
            'curp.unique' => 'La CURP ya está registrada.',

            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'genero.required' => 'El género es obligatorio.',
            'genero.in' => 'El género seleccionado no es válido.',

            'rfc.unique' => 'El RFC ya está registrado.',
            'rfc.min' => 'El RFC debe tener al menos 12 caracteres.',
            'rfc.max' => 'El RFC no debe superar 13 caracteres.',

            'foto.image' => 'La foto debe ser una imagen válida.',
            'foto.max' => 'La foto no debe superar los 2MB.',
        ];
    }

    // =========================
    // CURP live (RENAPO)
    // =========================
    public function updatedCurp($value): void
    {
        $curp = strtoupper(trim((string) $value));
        $this->curp = $curp;

        $this->curpError = null;

        if (strlen($curp) < 18) {
            $this->resetAutollenadoRenapo();
            $this->ultimaCurpConsultada = null;
            return;
        }

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
            'datosCurp',
        ]);
    }

    public function consultarCurp(): void
    {
        if (!$this->curp || strlen($this->curp) !== 18)
            return;

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
                'text' => $data['detail'] ?? null,
                'icon' => 'error',
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
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
            return;
        }

        $this->nombre = $this->titleCaseNombre($info['Nombres'] ?? '');
        $this->apellido_paterno = $this->titleCaseNombre($info['ApellidoPaterno'] ?? '');
        $this->apellido_materno = $this->titleCaseNombre($info['ApellidoMaterno'] ?? '');

        $fecha = $info['FechaNacimiento'] ?? null;
        $this->fecha_nacimiento = $fecha ? date('Y-m-d', strtotime($fecha)) : null;

        $sexo = $info['ClaveSexo'] ?? null;
        $this->genero = in_array($sexo, ['H', 'M'], true) ? $sexo : null;

        $this->ultimaCurpConsultada = $this->curp;
    }

    // =========================
    // Autollenado desde CURP PDF (SOLO)
    // =========================
    public function autollenarDesdeCurpPdf(): void
    {
        $this->autollenadoError = null;

        $this->validate([
            'pdf_curp' => 'required|file|mimes:pdf|max:51200',
        ], [
            'pdf_curp.required' => 'Sube el PDF del CURP.',
            'pdf_curp.mimes' => 'El archivo debe ser PDF.',
        ]);

        try {
            Storage::disk('local')->makeDirectory('tmp/curp-extract');

            $curpPath = $this->pdf_curp->store('tmp/curp-extract', 'local');
            $absCurp = Storage::disk('local')->path($curpPath);

            if (!file_exists($absCurp)) {
                throw new \RuntimeException("No se encontró el PDF en disco: {$absCurp}");
            }

            /** @var AzureDocumentIntelligence $azure */
            $azure = app(AzureDocumentIntelligence::class);

            /** @var CurpPdfParser $curpParser */
            $curpParser = app(CurpPdfParser::class);

            // 1) OCR con Azure (texto)
            $text = $azure->extractText($absCurp);

            // 2) Parsear texto (CURP, nombres, etc.)
            $data = $curpParser->parse($text);

            // 3) Aplicar al formulario
            $this->applyExtractedCurpToForm($data, $this->autollenar_forzar);

            @unlink($absCurp);

            $this->dispatch('swal', [
                'title' => 'Datos extraídos del CURP PDF (Azure).',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        } catch (\Throwable $e) {
            $this->autollenadoError = $e->getMessage();

            $this->dispatch('swal', [
                'title' => 'No se pudo extraer del CURP PDF',
                'text' => $this->autollenadoError,
                'icon' => 'error',
                'position' => 'top-end',
            ]);
        }
    }


    private function applyExtractedCurpToForm(array $data, bool $force = false): void
    {
        $set = function (string $prop, $value) use ($force) {
            if ($value === null || $value === '')
                return;

            $current = $this->{$prop} ?? null;
            $empty = is_string($current) ? trim($current) === '' : empty($current);

            if ($force || $empty) {
                $this->{$prop} = $value;
            }
        };

        // CURP
        $curp = $data['curp'] ?? null;
        if ($curp) {
            $set('curp', strtoupper(trim((string) $curp)));
        }

        // ✅ Si ya vienen partes (lo ideal)
        $nombres = trim((string) ($data['nombres'] ?? ''));
        $apPat = trim((string) ($data['apellido_paterno'] ?? ''));
        $apMat = trim((string) ($data['apellido_materno'] ?? ''));

        if ($nombres !== '' || $apPat !== '' || $apMat !== '') {
            if ($nombres !== '')
                $set('nombre', $this->titleCaseNombre($nombres));
            if ($apPat !== '')
                $set('apellido_paterno', $this->titleCaseNombre($apPat));
            if ($apMat !== '')
                $set('apellido_materno', $this->titleCaseNombre($apMat));
            return;
        }

        // Fallback: nombre completo (si no hubo etiquetas)
        $full = trim((string) ($data['nombre_completo'] ?? ($data['nombre'] ?? '')));
        if ($full !== '') {
            [$nombres2, $apPat2, $apMat2] = $this->splitNombreCompletoMxSmart($full);

            $set('nombre', $this->titleCaseNombre($nombres2));
            $set('apellido_paterno', $this->titleCaseNombre($apPat2));
            $set('apellido_materno', $apMat2 ? $this->titleCaseNombre($apMat2) : null);
        }
    }


    // =========================
    // Helpers
    // =========================
    private function titleCaseNombre(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '')
            return '';

        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        // Title Case multibyte
        $value = mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

        // Partículas comunes en minúscula (cuando van en medio)
        foreach ([' De ', ' Del ', ' La ', ' Las ', ' Los ', ' Y ', ' E '] as $w) {
            $value = str_replace($w, mb_strtolower($w, 'UTF-8'), $value);
        }

        return $value;
    }

    private function splitNombreCompletoMxSmart(string $full): array
    {
        $full = preg_replace('/\s+/', ' ', trim($full)) ?? trim($full);
        if ($full === '')
            return ['', '', null];

        $tokens = preg_split('/\s+/', $full) ?: [];
        if (count($tokens) < 3)
            return [$full, '', null];

        // partículas que pueden formar apellidos compuestos
        $particles = ['DE', 'DEL', 'LA', 'LAS', 'LOS', 'Y', 'E', 'VON', 'VAN', 'MC', 'MAC'];

        $takeSurname = function (&$arr) use ($particles) {
            $surname = [];

            // toma el último token como base
            $surname[] = array_pop($arr);

            // si antes hay partículas, también pertenecen al apellido (ej: "DE LA CRUZ")
            while (!empty($arr)) {
                $peek = strtoupper((string) end($arr));
                if (in_array($peek, $particles, true)) {
                    array_unshift($surname, array_pop($arr));
                } else {
                    break;
                }
            }

            return implode(' ', $surname);
        };

        // Materno (al final)
        $apMat = $takeSurname($tokens);

        // Paterno (lo siguiente al final)
        $apPat = $takeSurname($tokens);

        // Resto -> nombres
        $nombres = implode(' ', $tokens);

        return [$nombres, $apPat, $apMat];
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
            'nombre' => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'foto' => $fotoPath,
            'curp' => strtoupper(trim((string) $this->curp)),
            'rfc' => $this->rfc ? strtoupper(trim((string) $this->rfc)) : null,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'genero' => $this->genero,
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

        $this->reset();
        $this->status = true;

        $this->dispatch('refreshPersonal');
    }

    public function render()
    {
        return view('livewire.personas.crear-personal');
    }
}
