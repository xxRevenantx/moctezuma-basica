<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GroqCurpExtractor
{
    private string $baseUrl;
    private string $model;
    private ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('groq.base_url', 'https://api.groq.com/openai/v1'), '/');
        $this->model = (string) config('groq.curp.model', config('groq.model', 'openai/gpt-oss-20b'));
        $this->apiKey = config('groq.api_key');
    }

    /**
     * @return array{
     *     curp:?string,
     *     nombres:?string,
     *     apellido_paterno:?string,
     *     apellido_materno:?string,
     *     nombre_completo:?string,
     *     fecha_nacimiento:?string,
     *     genero:?string,
     *     entidad_nacimiento:?string,
     *     confianza:int
     * }
     */
    public function extract(string $text): array
    {
        $this->validateConfiguration();

        $text = $this->sanitizeInput($text);

        if ($text === '') {
            throw new RuntimeException('No hay texto del PDF para enviar a GroqCloud.');
        }

        $system = <<<'PROMPT'
Eres un extractor estricto de datos de documentos oficiales de CURP de México.

Devuelve únicamente JSON conforme al esquema solicitado.

Reglas obligatorias:
- No inventes información.
- Usa null cuando el dato no aparezca o no sea suficientemente claro.
- La CURP debe tener exactamente 18 caracteres en mayúsculas.
- fecha_nacimiento debe usar el formato YYYY-MM-DD.
- genero debe ser H, M o null.
- Separa cuidadosamente nombres y apellidos mexicanos, incluidos apellidos compuestos.
- confianza debe ser un entero de 0 a 100 según la claridad del documento.
- No incluyas explicaciones, Markdown ni texto adicional.
PROMPT;

        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->asJson()
                ->connectTimeout((int) config('groq.connect_timeout', 8))
                ->timeout((int) config('groq.request_timeout', 60))
                ->retry(2, 700, throw: false)
                ->post($this->baseUrl . '/chat/completions', [
                    'model' => $this->model,
                    'temperature' => (float) config('groq.curp.temperature', 0),
                    'max_completion_tokens' => (int) config('groq.curp.max_tokens', 700),
                    'reasoning_effort' => 'low',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $system,
                        ],
                        [
                            'role' => 'user',
                            'content' => "Extrae los datos del siguiente texto obtenido de un PDF de CURP:\n\n{$text}",
                        ],
                    ],
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'datos_curp',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'curp' => ['type' => ['string', 'null']],
                                    'nombres' => ['type' => ['string', 'null']],
                                    'apellido_paterno' => ['type' => ['string', 'null']],
                                    'apellido_materno' => ['type' => ['string', 'null']],
                                    'nombre_completo' => ['type' => ['string', 'null']],
                                    'fecha_nacimiento' => ['type' => ['string', 'null']],
                                    'genero' => ['type' => ['string', 'null']],
                                    'entidad_nacimiento' => ['type' => ['string', 'null']],
                                    'confianza' => [
                                        'type' => 'integer',
                                        'minimum' => 0,
                                        'maximum' => 100,
                                    ],
                                ],
                                'required' => [
                                    'curp',
                                    'nombres',
                                    'apellido_paterno',
                                    'apellido_materno',
                                    'nombre_completo',
                                    'fecha_nacimiento',
                                    'genero',
                                    'entidad_nacimiento',
                                    'confianza',
                                ],
                                'additionalProperties' => false,
                            ],
                        ],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'No fue posible conectarse con GroqCloud. Revisa la conexión a internet.',
                previous: $e
            );
        }

        if (!$response->successful()) {
            throw new RuntimeException($this->httpMessage($response));
        }

        $content = trim((string) $response->json('choices.0.message.content', ''));
        $data = json_decode($content, true);

        if (!is_array($data)) {
            throw new RuntimeException('GroqCloud devolvió una respuesta que no es JSON válido.');
        }

        return $this->normalize($data);
    }

    public function isConfigured(): bool
    {
        return (bool) config('groq.enabled', true)
            && (bool) config('groq.curp.enabled', true)
            && filled($this->apiKey);
    }

    private function validateConfiguration(): void
    {
        if (!(bool) config('groq.enabled', true)) {
            throw new RuntimeException('GroqCloud está desactivado en la configuración.');
        }

        if (!(bool) config('groq.curp.enabled', true)) {
            throw new RuntimeException('La extracción de CURP con GroqCloud está desactivada.');
        }

        if (blank($this->apiKey)) {
            throw new RuntimeException('Falta configurar GROQ_API_KEY en el archivo .env.');
        }
    }

    private function sanitizeInput(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return mb_substr($text, 0, (int) config('groq.curp.max_input_chars', 12000));
    }

    private function normalize(array $data): array
    {
        $curp = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) ($data['curp'] ?? '')) ?? '');

        if (!$this->validCurp($curp)) {
            $curp = null;
        }

        $gender = strtoupper(trim((string) ($data['genero'] ?? '')));
        $gender = in_array($gender, ['H', 'M'], true) ? $gender : null;

        return [
            'curp' => $curp,
            'nombres' => $this->cleanText($data['nombres'] ?? null),
            'apellido_paterno' => $this->cleanText($data['apellido_paterno'] ?? null),
            'apellido_materno' => $this->cleanText($data['apellido_materno'] ?? null),
            'nombre_completo' => $this->cleanText($data['nombre_completo'] ?? null),
            'fecha_nacimiento' => $this->validDate($data['fecha_nacimiento'] ?? null),
            'genero' => $gender,
            'entidad_nacimiento' => $this->cleanText($data['entidad_nacimiento'] ?? null),
            'confianza' => max(0, min(100, (int) ($data['confianza'] ?? 0))),
        ];
    }

    private function cleanText(mixed $value): ?string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value !== '' ? $value : null;
    }

    private function validDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
            return null;
        }

        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])
            ? $value
            : null;
    }

    private function validCurp(string $curp): bool
    {
        return preg_match('/^[A-Z][AEIOUX][A-Z]{2}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/', $curp) === 1;
    }

    private function httpMessage(Response $response): string
    {
        $detail = trim((string) ($response->json('error.message') ?? $response->json('message') ?? ''));

        return match ($response->status()) {
            400 => $detail !== '' ? 'GroqCloud rechazó la solicitud: ' . $detail : 'GroqCloud rechazó el formato enviado.',
            401 => 'La API key de GroqCloud es inválida o fue revocada.',
            403 => 'La cuenta de GroqCloud no tiene permiso para usar el modelo configurado.',
            404 => "El modelo {$this->model} no está disponible en GroqCloud.",
            413 => 'El texto del PDF es demasiado grande para procesarse.',
            429 => 'Se alcanzó temporalmente el límite de GroqCloud. Intenta nuevamente en unos segundos.',
            500, 502, 503, 504 => 'GroqCloud presenta una interrupción temporal. Intenta nuevamente más tarde.',
            default => $detail !== ''
                ? 'GroqCloud no pudo procesar la CURP: ' . $detail
                : 'GroqCloud no pudo procesar la CURP. Código HTTP: ' . $response->status() . '.',
        };
    }
}
