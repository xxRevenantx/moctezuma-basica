<?php

namespace App\Livewire\Tutor;

use App\Models\Tutor;
use App\Rules\CurpMexicana;
use App\Services\CurpDataDecoder;
use App\Services\TutorDocumentOcrService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class CrearTutor extends Component
{
    use WithFileUploads;

    public bool $sin_curp = false;
    public ?string $curp = null;
    public ?string $identificador_alternativo = null;
    public ?string $motivo_sin_curp = null;
    public ?string $nombre = null;
    public ?string $apellido_paterno = null;
    public ?string $apellido_materno = null;
    public ?string $genero = null;
    public ?string $fecha_nacimiento = null;
    public ?string $ciudad_nacimiento = null;
    public ?string $estado_nacimiento = null;
    public ?string $municipio_nacimiento = null;
    public ?string $calle = null;
    public ?string $colonia = null;
    public ?string $ciudad = null;
    public ?string $municipio = null;
    public ?string $estado = null;
    public ?string $numero = null;
    public ?string $codigo_postal = null;
    public ?string $telefono_casa = null;
    public ?string $telefono_celular = null;
    public ?string $correo_electronico = null;

    // Lector local de INE / constancia de CURP.
    public $documento_ocr = null;
    public string $tipo_documento_ocr = 'ine';
    public bool $ocr_reemplazar = false;
    public array $ocr_resultado = [];
    public array $ocr_campos_seleccionados = [];
    public array $ocr_capacidades = [];
    public ?string $ocr_error = null;
    public ?string $curp_local_mensaje = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccess('alumnos.crear'), 403);
        $this->cargarCapacidadesOcr();
    }

    protected function rules(): array
    {
        return [
            'sin_curp' => ['boolean'],
            'curp' => [Rule::requiredIf(! $this->sin_curp), 'nullable', 'string', 'size:18', new CurpMexicana(), 'unique:tutores,curp'],
            'identificador_alternativo' => [Rule::requiredIf($this->sin_curp), 'nullable', 'string', 'max:80', 'unique:tutores,identificador_alternativo'],
            'motivo_sin_curp' => [Rule::requiredIf($this->sin_curp), 'nullable', 'string', 'min:5', 'max:255'],
            'nombre' => ['required', 'string', 'max:255'],
            'apellido_paterno' => ['required', 'string', 'max:255'],
            'apellido_materno' => ['nullable', 'string', 'max:255'],
            'genero' => ['nullable', Rule::in(['M', 'F', 'O'])],
            'fecha_nacimiento' => ['nullable', 'date'],
            'ciudad_nacimiento' => ['nullable', 'string', 'max:255'],
            'estado_nacimiento' => ['nullable', 'string', 'max:255'],
            'municipio_nacimiento' => ['nullable', 'string', 'max:255'],
            'calle' => ['nullable', 'string', 'max:255'],
            'colonia' => ['nullable', 'string', 'max:255'],
            'ciudad' => ['nullable', 'string', 'max:255'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'codigo_postal' => ['nullable', 'regex:/^[0-9]{5}$/'],
            'telefono_casa' => ['nullable', 'string', 'max:20'],
            'telefono_celular' => ['nullable', 'string', 'max:20'],
            'correo_electronico' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function updatedSinCurp(bool $sinCurp): void
    {
        if ($sinCurp) {
            $this->curp = null;
            $this->curp_local_mensaje = null;
        } else {
            $this->identificador_alternativo = null;
            $this->motivo_sin_curp = null;
        }

        $this->resetValidation(['curp', 'identificador_alternativo', 'motivo_sin_curp']);
    }

    public function updatedCurp(): void
    {
        $this->curp = mb_strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $this->curp) ?: '');
        $this->curp_local_mensaje = null;

        // Mientras está incompleta no mostramos error ni consultamos servicios externos.
        if ($this->sin_curp || blank($this->curp) || mb_strlen($this->curp) < 18) {
            $this->resetValidation('curp');
            return;
        }

        $this->validateOnly('curp');
        $this->aplicarInterpretacionLocalCurp();
    }

    public function updatedDocumentoOcr(): void
    {
        $this->ocr_error = null;
        $this->ocr_resultado = [];
        $this->ocr_campos_seleccionados = [];
        $this->resetValidation('documento_ocr');
    }

    public function updatedTipoDocumentoOcr(): void
    {
        $this->ocr_error = null;
        $this->ocr_resultado = [];
        $this->ocr_campos_seleccionados = [];
    }

    public function analizarDocumentoTutor(): void
    {
        $this->ocr_error = null;
        $this->ocr_resultado = [];
        $this->ocr_campos_seleccionados = [];

        $maxKb = max(1024, (int) config('tutor_ocr.max_file_kb', 12288));

        $this->validate([
            'tipo_documento_ocr' => ['required', Rule::in(['ine', 'curp'])],
            'documento_ocr' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:' . $maxKb],
        ], [
            'documento_ocr.required' => 'Selecciona una fotografía, imagen o PDF.',
            'documento_ocr.mimes' => 'El documento debe ser PDF, JPG, JPEG, PNG o WEBP.',
            'documento_ocr.max' => 'El documento no debe superar ' . round($maxKb / 1024, 1) . ' MB.',
        ]);

        $storedPath = null;

        try {
            Storage::disk('local')->makeDirectory('tmp/tutor-ocr/uploads');
            $storedPath = $this->documento_ocr->store('tmp/tutor-ocr/uploads', 'local');
            $absolutePath = Storage::disk('local')->path($storedPath);

            /** @var TutorDocumentOcrService $service */
            $service = app(TutorDocumentOcrService::class);
            $result = $service->analyze($absolutePath, $this->tipo_documento_ocr);

            $this->ocr_resultado = [
                'campos' => $result['campos'] ?? [],
                'advertencias' => $result['advertencias'] ?? [],
                'confianza' => $result['confianza'] ?? null,
                'metodo' => $result['metodo'] ?? 'Lector local',
                'texto' => $result['texto'] ?? null,
            ];

            $this->ocr_campos_seleccionados = collect($this->ocr_resultado['campos'])
                ->filter(fn ($value) => filled($value))
                ->keys()
                ->values()
                ->all();

            $this->dispatch('swal', [
                'title' => 'Documento analizado localmente',
                'text' => 'Revisa los campos detectados y presiona “Aplicar al formulario”.',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        } catch (\Throwable $e) {
            report($e);
            $this->ocr_error = $e->getMessage();

            $this->dispatch('swal', [
                'title' => 'No se pudo leer el documento',
                'text' => $this->ocr_error,
                'icon' => 'error',
                'position' => 'top-end',
            ]);
        } finally {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }
            $this->documento_ocr = null;
        }
    }

    public function aplicarDatosOcr(): void
    {
        $fields = $this->ocr_resultado['campos'] ?? [];
        if ($fields === [] || $this->ocr_campos_seleccionados === []) {
            $this->dispatch('swal', [
                'title' => 'No hay campos seleccionados',
                'text' => 'Selecciona al menos un dato detectado.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
            return;
        }

        $allowed = array_keys($this->etiquetasCamposOcr());
        $applied = 0;
        $omitted = 0;

        foreach ($this->ocr_campos_seleccionados as $field) {
            if (! in_array($field, $allowed, true)) {
                continue;
            }

            $value = $fields[$field] ?? null;
            if (blank($value)) {
                continue;
            }

            $current = $this->{$field} ?? null;
            $isEmpty = is_string($current) ? trim($current) === '' : blank($current);

            if (! $this->ocr_reemplazar && ! $isEmpty) {
                $omitted++;
                continue;
            }

            if (in_array($field, ['nombre', 'apellido_paterno', 'apellido_materno'], true)) {
                $value = $this->titleCase((string) $value);
            }

            if ($field === 'curp') {
                $value = mb_strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $value) ?: '');
                $this->sin_curp = false;
                $this->identificador_alternativo = null;
                $this->motivo_sin_curp = null;
            }

            $this->{$field} = $value;
            $applied++;
        }

        if (filled($this->curp) && mb_strlen((string) $this->curp) === 18) {
            try {
                $this->validateOnly('curp');
                $this->aplicarInterpretacionLocalCurp();
            } catch (ValidationException) {
                // Livewire ya presenta el error específico en el campo CURP.
            }
        }

        $this->dispatch('tutor-ocr-aplicado');
        $message = "Se aplicaron {$applied} campos.";
        if ($omitted > 0) {
            $message .= " Se conservaron {$omitted} valores que ya estaban capturados.";
        }

        $this->dispatch('swal', [
            'title' => 'Datos aplicados al formulario',
            'text' => $message . ' Verifica la información antes de guardar.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function limpiarResultadoOcr(): void
    {
        $this->documento_ocr = null;
        $this->ocr_resultado = [];
        $this->ocr_campos_seleccionados = [];
        $this->ocr_error = null;
        $this->ocr_reemplazar = false;
        $this->resetValidation('documento_ocr');
    }

    public function guardar(): void
    {
        $this->normalizar();
        $data = $this->validate();

        if (blank($data['telefono_celular']) && blank($data['telefono_casa']) && blank($data['correo_electronico'])) {
            throw ValidationException::withMessages([
                'telefono_celular' => 'Captura al menos teléfono celular, teléfono de casa o correo electrónico.',
            ]);
        }

        $tutor = DB::transaction(function () use ($data): Tutor {
            return Tutor::query()->create([
                ...$data,
                'curp' => blank($data['curp']) ? null : $data['curp'],
                'identificador_alternativo' => blank($data['identificador_alternativo']) ? null : $data['identificador_alternativo'],
                'motivo_sin_curp' => blank($data['motivo_sin_curp']) ? null : $data['motivo_sin_curp'],
                // Campo legado: el parentesco correcto se captura al relacionar al tutor con cada alumno.
                'parentesco' => 'NO ESPECIFICADO',
                'activo' => true,
            ]);
        });

        $this->dispatch('swal', [
            'title' => 'Responsable creado correctamente',
            'text' => 'Ahora puedes relacionarlo con uno o varios alumnos y definir el parentesco de cada relación.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('tutorRegistered', tutor: $tutor->id);
        $this->limpiar();
        $this->dispatch('refreshTutor');
    }

    public function limpiar(): void
    {
        $this->reset();
        $this->tipo_documento_ocr = 'ine';
        $this->cargarCapacidadesOcr();
        $this->resetValidation();
    }

    private function aplicarInterpretacionLocalCurp(): void
    {
        /** @var CurpDataDecoder $decoder */
        $decoder = app(CurpDataDecoder::class);
        $decoded = $decoder->decode($this->curp);

        if (! $decoded['valida']) {
            return;
        }

        if (blank($this->fecha_nacimiento) && filled($decoded['fecha_nacimiento'])) {
            $this->fecha_nacimiento = $decoded['fecha_nacimiento'];
        }
        if (blank($this->genero) && filled($decoded['genero'])) {
            $this->genero = $decoded['genero'];
        }
        if (blank($this->estado_nacimiento) && filled($decoded['estado_nacimiento'])) {
            $this->estado_nacimiento = $decoded['estado_nacimiento'];
        }

        $this->curp_local_mensaje = $decoded['mensaje'];
    }

    private function cargarCapacidadesOcr(): void
    {
        try {
            $capabilities = app(TutorDocumentOcrService::class)->capabilities();
            $this->ocr_capacidades = [
                'enabled' => (bool) ($capabilities['enabled'] ?? false),
                'tesseract' => (bool) ($capabilities['tesseract'] ?? false),
                'pdftoppm' => (bool) ($capabilities['pdftoppm'] ?? false),
                'imagemagick' => (bool) ($capabilities['imagemagick'] ?? false),
                'pdf_text' => true,
            ];
        } catch (\Throwable) {
            $this->ocr_capacidades = [
                'enabled' => false,
                'tesseract' => false,
                'pdftoppm' => false,
                'imagemagick' => false,
                'pdf_text' => true,
            ];
        }
    }

    private function normalizar(): void
    {
        $this->curp = mb_strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $this->curp) ?: '');
        $this->identificador_alternativo = mb_strtoupper(trim((string) $this->identificador_alternativo));

        if ($this->sin_curp) {
            $this->curp = null;
        } else {
            $this->identificador_alternativo = null;
            $this->motivo_sin_curp = null;
        }

        foreach (['nombre', 'apellido_paterno', 'apellido_materno'] as $campo) {
            $this->{$campo} = $this->titleCase((string) $this->{$campo});
        }

        foreach (['motivo_sin_curp', 'ciudad_nacimiento', 'estado_nacimiento', 'municipio_nacimiento', 'calle', 'colonia', 'ciudad', 'municipio', 'estado', 'numero', 'codigo_postal', 'telefono_casa', 'telefono_celular', 'correo_electronico'] as $campo) {
            $valor = trim((string) $this->{$campo});
            $this->{$campo} = $valor === '' ? null : $valor;
        }
    }

    private function titleCase(string $value): string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));
        if ($value === '') {
            return '';
        }

        $value = mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        foreach ([' De ', ' Del ', ' La ', ' Las ', ' Los ', ' Y ', ' E '] as $particle) {
            $value = str_replace($particle, mb_strtolower($particle, 'UTF-8'), $value);
        }

        return $value;
    }

    /** @return array<string, string> */
    private function etiquetasCamposOcr(): array
    {
        return [
            'curp' => 'CURP',
            'nombre' => 'Nombre(s)',
            'apellido_paterno' => 'Apellido paterno',
            'apellido_materno' => 'Apellido materno',
            'fecha_nacimiento' => 'Fecha de nacimiento',
            'genero' => 'Género',
            'ciudad_nacimiento' => 'Ciudad de nacimiento',
            'municipio_nacimiento' => 'Municipio de nacimiento',
            'estado_nacimiento' => 'Estado de nacimiento',
            'calle' => 'Calle',
            'numero' => 'Número',
            'colonia' => 'Colonia',
            'ciudad' => 'Ciudad',
            'municipio' => 'Municipio',
            'estado' => 'Estado',
            'codigo_postal' => 'Código postal',
        ];
    }

    public function render()
    {
        return view('livewire.tutor.crear-tutor', [
            'etiquetasCamposOcr' => $this->etiquetasCamposOcr(),
        ]);
    }
}
