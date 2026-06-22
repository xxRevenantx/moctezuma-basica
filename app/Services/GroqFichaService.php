<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GroqFichaService
{
    private string $baseUrl;
    private string $model;
    private ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('groq.base_url', 'https://api.groq.com/openai/v1'), '/');
        $this->model = (string) config('groq.model', 'llama-3.1-8b-instant');
        $this->apiKey = config('groq.api_key');
    }

    public function model(): string
    {
        return $this->model;
    }

    /**
     * @return array{
     *     disponible: bool,
     *     modelo_disponible: bool,
     *     modelo: string,
     *     mensaje: string,
     *     modelos: array<int, string>
     * }
     */
    public function estado(): array
    {
        if (!(bool) config('groq.enabled', true)) {
            return $this->estadoError('GroqCloud está desactivado en la configuración.');
        }

        if (blank($this->apiKey)) {
            return $this->estadoError('Falta configurar GROQ_API_KEY en el archivo .env.');
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->connectTimeout((int) config('groq.connect_timeout', 8))
                ->timeout((int) config('groq.status_timeout', 15))
                ->get($this->baseUrl . '/models');
        } catch (ConnectionException) {
            return $this->estadoError('No fue posible conectarse con GroqCloud. Revisa tu conexión a internet.');
        }

        if (!$response->successful()) {
            return $this->estadoError($this->mensajeHttp($response, 'No se pudo verificar GroqCloud.'));
        }

        $modelos = collect($response->json('data', []))
            ->pluck('id')
            ->filter(fn($id) => is_string($id) && $id !== '')
            ->values()
            ->all();

        $modeloDisponible = in_array($this->model, $modelos, true);

        return [
            'disponible' => true,
            'modelo_disponible' => $modeloDisponible,
            'modelo' => $this->model,
            'mensaje' => $modeloDisponible
                ? 'GroqCloud está listo para generar descripciones.'
                : "La API funciona, pero el modelo {$this->model} no aparece entre los modelos activos.",
            'modelos' => $modelos,
        ];
    }

    /**
     * Genera una descripción pedagógica mediante GroqCloud.
     *
     * @param array<int, string|null> $datosSensibles
     */
    public function generarDescripcion(
        string $campo,
        string $periodo,
        string $grado,
        string $referenciaAlumno,
        string $observaciones,
        string $descripcionActual = '',
        string $contextoAdicional = '',
        array $datosSensibles = []
    ): string {
        if (!(bool) config('groq.enabled', true)) {
            throw new RuntimeException('GroqCloud está desactivado en la configuración.');
        }

        if (blank($this->apiKey)) {
            throw new RuntimeException('Falta configurar GROQ_API_KEY en el archivo .env.');
        }

        $observaciones = $this->anonimizar($observaciones, $datosSensibles);
        $descripcionActual = $this->anonimizar($descripcionActual, $datosSensibles);
        $contextoAdicional = $this->anonimizar($contextoAdicional, $datosSensibles);

        $esRecomendacion = mb_strtolower($campo) === 'recomendaciones';

        $system = <<<'PROMPT'
Eres asistente de una educadora de preescolar en México.

Redacta textos pedagógicos profesionales, claros, respetuosos y fáciles de comprender por madres, padres o tutores.

Reglas obligatorias:
- Escribe en español de México.
- No inventes logros, dificultades, conductas ni información ausente.
- No realices diagnósticos médicos, psicológicos, neurológicos ni del desarrollo.
- No uses etiquetas negativas, humillantes o discriminatorias.
- Evita expresiones absolutas como "nunca", "siempre", "incapaz" o "problemático".
- Presenta primero avances observados y después aspectos que requieren acompañamiento.
- Mantén un tono positivo, objetivo, formativo y realista.
- No menciones nombres, apellidos, CURP, matrícula, domicilio, teléfono ni otros identificadores.
- No incluyas títulos, saludos, listas, Markdown, comillas ni explicaciones.
- Devuelve únicamente el texto final en uno o dos párrafos.
PROMPT;

        $tipoSolicitud = $esRecomendacion
            ? 'Redacta recomendaciones concretas y realistas basadas únicamente en la información proporcionada. Usa entre 70 y 120 palabras.'
            : 'Redacta o mejora una descripción del campo formativo. Usa entre 90 y 150 palabras e incluye una recomendación breve al final cuando sea pertinente.';

        $observacionesPrompt = $this->valorOIndicacion($observaciones);
        $descripcionPrompt = $this->valorOIndicacion($descripcionActual);
        $contextoPrompt = $this->valorOIndicacion($contextoAdicional);

        $prompt = <<<PROMPT
Solicitud:
{$tipoSolicitud}

Referencia gramatical: {$referenciaAlumno}
Campo formativo: {$campo}
Periodo: {$periodo}
Grado: {$grado}

Observaciones breves de la educadora:
{$observacionesPrompt}

Texto actual que puede conservarse o mejorarse:
{$descripcionPrompt}

Contexto adicional autorizado:
{$contextoPrompt}

Utiliza solamente la información anterior y devuelve únicamente el texto final.
PROMPT;

        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->asJson()
                ->connectTimeout((int) config('groq.connect_timeout', 8))
                ->timeout((int) config('groq.request_timeout', 60))
                ->retry(2, 800, throw: false)
                ->post($this->baseUrl . '/chat/completions', [
                    'model' => $this->model,
                    'temperature' => (float) config('groq.temperature', 0.35),
                    'max_tokens' => (int) config('groq.max_tokens', 450),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $system,
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException(
                'No fue posible conectarse con GroqCloud. Revisa tu conexión a internet y vuelve a intentarlo.'
            );
        }

        if (!$response->successful()) {
            throw new RuntimeException($this->mensajeHttp($response, 'GroqCloud no pudo generar la descripción.'));
        }

        $texto = trim((string) $response->json('choices.0.message.content', ''));

        if ($texto === '') {
            throw new RuntimeException('GroqCloud no devolvió ninguna descripción.');
        }

        return $this->limpiarRespuesta($texto);
    }

    /**
     * @param array<int, string|null> $datosSensibles
     */
    private function anonimizar(string $texto, array $datosSensibles): string
    {
        $texto = html_entity_decode(strip_tags($texto), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('/\s+/u', ' ', trim($texto)) ?? '';

        foreach ($datosSensibles as $dato) {
            $dato = trim((string) $dato);

            if ($dato === '' || mb_strlen($dato) < 3) {
                continue;
            }

            $texto = str_ireplace($dato, '[ESTUDIANTE]', $texto);
        }

        $patrones = [
            '/\b[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d\b/iu', // CURP
            '/\b[\w.%+\-]+@[\w.\-]+\.[A-Z]{2,}\b/iu',     // correo
            '/\b(?:\+?52\s*)?(?:\d[\s\-()]*){10}\b/u', // teléfono MX
            '/\b\d{5}\b/u',                                // código postal
        ];

        $texto = preg_replace($patrones, '[DATO OMITIDO]', $texto) ?? $texto;

        return mb_substr($texto, 0, 7000);
    }

    private function limpiarRespuesta(string $texto): string
    {
        $texto = trim(strip_tags($texto));
        $texto = preg_replace('/^```(?:text|markdown)?\s*/iu', '', $texto) ?? $texto;
        $texto = preg_replace('/\s*```$/u', '', $texto) ?? $texto;
        $texto = preg_replace('/^(descripción|recomendaciones?)\s*:\s*/iu', '', $texto) ?? $texto;
        $texto = preg_replace('/[ \t]+/u', ' ', $texto) ?? $texto;
        $texto = preg_replace('/\R{3,}/u', "\n\n", $texto) ?? $texto;

        return trim($texto, " \t\n\r\0\x0B\"");
    }

    private function valorOIndicacion(string $texto): string
    {
        return trim($texto) !== ''
            ? trim($texto)
            : '(No se proporcionó información en este apartado)';
    }

    private function mensajeHttp(Response $response, string $predeterminado): string
    {
        $detalle = (string) (
            $response->json('error.message')
            ?? $response->json('message')
            ?? ''
        );

        return match ($response->status()) {
            401 => 'La API key de GroqCloud es inválida o fue revocada.',
            403 => 'La cuenta de GroqCloud no tiene permiso para realizar esta solicitud.',
            404 => "El modelo {$this->model} no está disponible. Consulta los modelos activos en GroqCloud.",
            429 => 'Se alcanzó temporalmente el límite gratuito de GroqCloud. Espera un momento y vuelve a intentarlo.',
            500, 502, 503, 504 => 'GroqCloud presenta una interrupción temporal. Vuelve a intentarlo más tarde.',
            default => $detalle !== ''
                ? $predeterminado . ' ' . $detalle
                : $predeterminado . ' Código HTTP: ' . $response->status() . '.',
        };
    }

    /**
     * @return array{
     *     disponible: bool,
     *     modelo_disponible: bool,
     *     modelo: string,
     *     mensaje: string,
     *     modelos: array<int, string>
     * }
     */
    private function estadoError(string $mensaje): array
    {
        return [
            'disponible' => false,
            'modelo_disponible' => false,
            'modelo' => $this->model,
            'mensaje' => $mensaje,
            'modelos' => [],
        ];
    }
}
