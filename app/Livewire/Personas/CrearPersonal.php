<?php

namespace App\Livewire\Personas;

use App\Models\Persona;
use App\Services\CurpService;
use App\Services\CurpPdfParser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\CurpPdfTextExtractor;
use App\Services\GroqCurpExtractor;
use App\Services\ImagenPersonalService;


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
    public ?string $autollenadoOrigen = null;
    public ?int $autollenadoConfianza = null;

    protected function rules(): array
    {
        return [
            'titulo' => 'required|string|max:10',
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',

            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

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
            'titulo',
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
        $this->autollenadoOrigen = null;
        $this->autollenadoConfianza = null;

        $this->validate([
            'pdf_curp' => 'required|file|mimes:pdf|max:51200',
        ], [
            'pdf_curp.required' => 'Sube el PDF de la CURP.',
            'pdf_curp.mimes' => 'El archivo debe estar en formato PDF.',
            'pdf_curp.max' => 'El PDF no debe superar los 50 MB.',
        ]);

        $storedPath = null;

        try {
            Storage::disk('local')->makeDirectory('tmp/curp-extract');

            $storedPath = $this->pdf_curp->store('tmp/curp-extract', 'local');
            $absolutePath = Storage::disk('local')->path($storedPath);

            /** @var CurpPdfTextExtractor $textExtractor */
            $textExtractor = app(CurpPdfTextExtractor::class);

            /** @var CurpPdfParser $localParser */
            $localParser = app(CurpPdfParser::class);

            /** @var GroqCurpExtractor $groqExtractor */
            $groqExtractor = app(GroqCurpExtractor::class);

            // 1. Lee localmente el texto seleccionable del PDF.
            $text = $textExtractor->extract($absolutePath);

            // 2. Mantiene un resultado local como respaldo determinista.
            $localData = $localParser->parse($text);
            $data = $localData;
            $origin = 'lector local';

            // 3. Groq mejora la separación de nombres y los datos estructurados.
            if ($groqExtractor->isConfigured()) {
                try {
                    $groqData = $groqExtractor->extract($text);
                    $data = $this->mergeExtractedData($groqData, $localData);
                    $origin = 'GroqCloud + lector local';
                    $this->autollenadoConfianza = (int) ($groqData['confianza'] ?? 0);
                } catch (\Throwable $groqError) {
                    report($groqError);

                    // Si Groq falla, el proceso continúa con el parser local.
                    $this->autollenadoError = 'GroqCloud no estuvo disponible; se utilizó el lector local. '
                        . $groqError->getMessage();
                }
            }

            $data = $this->completeDataFromCurp($data);

            if (empty($data['curp']) || !$this->isValidCurp((string) $data['curp'])) {
                throw new \RuntimeException(
                    'No fue posible localizar una CURP válida de 18 caracteres en el documento.'
                );
            }

            if (empty($data['nombres']) && empty($data['nombre_completo'])) {
                throw new \RuntimeException(
                    'Se encontró la CURP, pero no fue posible identificar correctamente el nombre del titular.'
                );
            }

            $minimumConfidence = (int) config('groq.curp.min_confidence', 65);
            if (
                $this->autollenadoConfianza !== null
                && $this->autollenadoConfianza < $minimumConfidence
            ) {
                $this->autollenadoError = "La extracción tuvo una confianza de {$this->autollenadoConfianza}%. Revisa los datos antes de guardar.";
            }

            $this->applyExtractedCurpToForm($data, $this->autollenar_forzar);
            $this->autollenadoOrigen = $origin;

            $this->dispatch('swal', [
                'title' => 'Datos de la CURP extraídos correctamente.',
                'text' => "Método utilizado: {$origin}.",
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        } catch (\Throwable $e) {
            report($e);
            $this->autollenadoError = $e->getMessage();

            $this->dispatch('swal', [
                'title' => 'No se pudo extraer la CURP del PDF',
                'text' => $this->autollenadoError,
                'icon' => 'error',
                'position' => 'top-end',
            ]);
        } finally {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }
        }
    }

    private function mergeExtractedData(array $preferred, array $fallback): array
    {
        $keys = [
            'curp',
            'nombres',
            'apellido_paterno',
            'apellido_materno',
            'nombre_completo',
            'fecha_nacimiento',
            'genero',
            'entidad_nacimiento',
            'confianza',
        ];

        $result = [];

        foreach ($keys as $key) {
            $preferredValue = $preferred[$key] ?? null;
            $fallbackValue = $fallback[$key] ?? null;

            $result[$key] = $preferredValue !== null && $preferredValue !== ''
                ? $preferredValue
                : $fallbackValue;
        }

        return $result;
    }

    private function completeDataFromCurp(array $data): array
    {
        $curp = strtoupper(preg_replace(
            '/[^A-Z0-9]/i',
            '',
            (string) ($data['curp'] ?? '')
        ) ?? '');

        $data['curp'] = $curp !== '' ? $curp : null;

        if (!$this->isValidCurp($curp)) {
            return $data;
        }

        $year = (int) substr($curp, 4, 2);
        $month = (int) substr($curp, 6, 2);
        $day = (int) substr($curp, 8, 2);
        $centuryMarker = substr($curp, 16, 1);
        $fullYear = ctype_digit($centuryMarker) ? 1900 + $year : 2000 + $year;

        if (empty($data['fecha_nacimiento']) && checkdate($month, $day, $fullYear)) {
            $data['fecha_nacimiento'] = sprintf('%04d-%02d-%02d', $fullYear, $month, $day);
        }

        $gender = substr($curp, 10, 1);
        if (empty($data['genero']) && in_array($gender, ['H', 'M'], true)) {
            $data['genero'] = $gender;
        }

        return $data;
    }

    private function isValidCurp(string $curp): bool
    {
        return preg_match(
            '/^[A-Z][AEIOUX][A-Z]{2}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/',
            strtoupper(trim($curp))
        ) === 1;
    }


    private function applyExtractedCurpToForm(array $data, bool $force = false): void
    {
        $set = function (string $property, mixed $value) use ($force): void {
            if ($value === null || (is_string($value) && trim($value) === '')) {
                return;
            }

            $current = $this->{$property} ?? null;
            $empty = is_string($current) ? trim($current) === '' : empty($current);

            if ($force || $empty) {
                $this->{$property} = $value;
            }
        };

        $curp = strtoupper(trim((string) ($data['curp'] ?? '')));
        if ($curp !== '') {
            $set('curp', $curp);
        }

        $names = trim((string) ($data['nombres'] ?? ''));
        $paternalSurname = trim((string) ($data['apellido_paterno'] ?? ''));
        $maternalSurname = trim((string) ($data['apellido_materno'] ?? ''));

        if ($names !== '' || $paternalSurname !== '' || $maternalSurname !== '') {
            $set('nombre', $this->titleCaseNombre($names));
            $set('apellido_paterno', $this->titleCaseNombre($paternalSurname));

            if ($maternalSurname !== '') {
                $set('apellido_materno', $this->titleCaseNombre($maternalSurname));
            }
        } else {
            $fullName = trim((string) ($data['nombre_completo'] ?? ($data['nombre'] ?? '')));

            if ($fullName !== '') {
                [$splitNames, $splitPaternal, $splitMaternal] = $this->splitNombreCompletoMxSmart($fullName);

                $set('nombre', $this->titleCaseNombre($splitNames));
                $set('apellido_paterno', $this->titleCaseNombre($splitPaternal));

                if ($splitMaternal) {
                    $set('apellido_materno', $this->titleCaseNombre($splitMaternal));
                }
            }
        }

        $date = trim((string) ($data['fecha_nacimiento'] ?? ''));
        if ($date !== '') {
            $set('fecha_nacimiento', $date);
        }

        $gender = strtoupper(trim((string) ($data['genero'] ?? '')));
        if (in_array($gender, ['H', 'M'], true)) {
            $set('genero', $gender);
        }

        $state = trim((string) ($data['entidad_nacimiento'] ?? ''));
        if ($state !== '') {
            $set('estado', $this->titleCaseNombre($state));
        }

        // Evita que updatedCurp() vuelva a consultar RENAPO al actualizar el input.
        if ($this->curp && strlen($this->curp) === 18) {
            $this->ultimaCurpConsultada = $this->curp;
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
    public function crearPersonal(ImagenPersonalService $imagenes): void
    {
        $this->validate();

        $fotoPath = $this->foto ? $imagenes->guardar($this->foto) : null;

        Persona::create([
            'titulo' => $this->titulo,
            'nombre' => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'foto' => $fotoPath,
            'curp' => strtoupper(trim((string) $this->curp)),
            'rfc' => $this->rfc ? strtoupper(trim((string) $this->rfc)) : null,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'genero' => $this->genero,
            'status' => $this->status,
            'estado_laboral' => $this->status ? 'activo' : 'baja',

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
