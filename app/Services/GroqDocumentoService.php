<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GroqDocumentoService
{
    private string $baseUrl;

    private string $model;

    private ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('groq.base_url', 'https://api.groq.com/openai/v1'), '/');
        $this->model = (string) config('groq.model', 'openai/gpt-oss-20b');
        $this->apiKey = config('groq.api_key');
    }

    /**
     * Genera, mejora o corrige el contenido HTML de una plantilla institucional.
     *
     * @param array<int, string> $variablesPermitidas
     */
    public function redactar(
        string $tipoDocumento,
        string $accion,
        string $titulo,
        string $instruccion,
        string $contenidoActual = '',
        array $variablesPermitidas = []
    ): string {
        $this->validarConfiguracion();

        $accion = mb_strtolower(trim($accion));

        if (!in_array($accion, ['generar', 'mejorar', 'corregir'], true)) {
            throw new RuntimeException('La acción solicitada para GroqCloud no es válida.');
        }

        $contenidoActual = $this->limpiarEntradaHtml($contenidoActual);
        $instruccion = $this->limpiarTexto($instruccion, 2500);
        $titulo = $this->limpiarTexto($titulo, 255);
        $tipoDocumento = $this->limpiarTexto($tipoDocumento, 80);

        $variablesPermitidas = collect($variablesPermitidas)
            ->map(fn($variable) => trim((string) $variable))
            ->filter(fn($variable) => preg_match('/^@[a-zA-Z0-9_]+$/', $variable) === 1)
            ->unique()
            ->values()
            ->all();

        $variablesObligatorias = $accion === 'generar'
            ? []
            : $this->extraerVariables($contenidoActual);

        $system = <<<'PROMPT'
Eres un asistente de redacción institucional para una escuela privada de México.

Tu tarea es redactar documentos escolares formales, claros, respetuosos y listos para editarse en TinyMCE.

Reglas obligatorias:
- Escribe en español de México.
- No inventes nombres, cargos, fechas, matrículas, CURP, calificaciones ni hechos.
- No agregues información personal real.
- Conserva literalmente todas las variables que empiecen con @.
- No cambies, traduzcas, pluralices ni elimines variables.
- Utiliza únicamente HTML sencillo compatible con TinyMCE.
- Etiquetas permitidas: p, br, strong, b, em, i, u, ul, ol, li, blockquote, h2, h3, h4, table, thead, tbody, tr, th y td.
- No uses Markdown, bloques de código, estilos CSS, scripts, enlaces, imágenes ni comentarios HTML.
- No incluyas el título del documento dentro del contenido, salvo que la instrucción lo solicite expresamente.
- Evita lenguaje excesivamente adornado, ambiguo o repetitivo.
- Devuelve solo el resultado solicitado dentro del campo HTML del JSON.
PROMPT;

        $descripcionAccion = match ($accion) {
            'generar' => 'Redacta desde cero el contenido del documento usando la instrucción y las variables disponibles.',
            'mejorar' => 'Mejora claridad, formalidad, coherencia y estructura del contenido actual sin cambiar su significado ni eliminar variables.',
            'corregir' => 'Corrige ortografía, puntuación, concordancia y redacción mínima. Conserva el significado, la estructura y todas las variables.',
        };

        $variablesTexto = $variablesPermitidas !== []
            ? implode(', ', $variablesPermitidas)
            : '(No se proporcionaron variables)';

        $contenidoTexto = $contenidoActual !== ''
            ? $contenidoActual
            : '(No existe contenido previo)';

        $instruccionTexto = $instruccion !== ''
            ? $instruccion
            : '(Sin instrucción adicional)';

        $prompt = <<<PROMPT
Tipo de documento: {$tipoDocumento}
Título administrativo: {$titulo}
Acción: {$accion}
Objetivo de la acción: {$descripcionAccion}

Instrucción del usuario:
{$instruccionTexto}

Variables permitidas:
{$variablesTexto}

Contenido HTML actual:
{$contenidoTexto}

Genera una versión profesional y reutilizable. No escribas datos reales en lugar de las variables.
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
                    'temperature' => $accion === 'corregir' ? 0.1 : 0.25,
                    'max_completion_tokens' => (int) config('groq.document_max_tokens', 1200),
                    'reasoning_effort' => 'low',
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
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'documento_institucional',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'html' => [
                                        'type' => 'string',
                                        'description' => 'Contenido HTML final del documento.',
                                    ],
                                ],
                                'required' => ['html'],
                                'additionalProperties' => false,
                            ],
                        ],
                    ],
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException(
                'No fue posible conectarse con GroqCloud. Revisa tu conexión a internet y vuelve a intentarlo.'
            );
        }

        if (!$response->successful()) {
            throw new RuntimeException($this->mensajeHttp(
                $response,
                'GroqCloud no pudo procesar el documento.'
            ));
        }

        $contenidoRespuesta = trim((string) $response->json('choices.0.message.content', ''));

        if ($contenidoRespuesta === '') {
            throw new RuntimeException('GroqCloud no devolvió contenido para el documento.');
        }

        $json = json_decode($contenidoRespuesta, true);

        if (!is_array($json) || !isset($json['html']) || !is_string($json['html'])) {
            throw new RuntimeException('GroqCloud devolvió una respuesta con formato inválido.');
        }

        $html = $this->sanitizarHtml($json['html']);

        if (trim(strip_tags($html)) === '') {
            throw new RuntimeException('El contenido generado quedó vacío después de validarlo.');
        }

        $variablesFaltantes = array_values(array_diff(
            $variablesObligatorias,
            $this->extraerVariables($html)
        ));

        if ($variablesFaltantes !== []) {
            throw new RuntimeException(
                'GroqCloud omitió variables obligatorias: ' . implode(', ', $variablesFaltantes) . '. No se aplicó el resultado.'
            );
        }

        return $html;
    }

    private function validarConfiguracion(): void
    {
        if (!(bool) config('groq.enabled', true)) {
            throw new RuntimeException('GroqCloud está desactivado en la configuración.');
        }

        if (blank($this->apiKey)) {
            throw new RuntimeException('Falta configurar GROQ_API_KEY en el archivo .env.');
        }
    }

    private function limpiarEntradaHtml(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        return mb_substr($html, 0, 20000);
    }

    private function limpiarTexto(string $texto, int $limite): string
    {
        $texto = html_entity_decode(strip_tags($texto), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('/\s+/u', ' ', trim($texto)) ?? '';

        return mb_substr($texto, 0, $limite);
    }

    /**
     * @return array<int, string>
     */
    private function extraerVariables(string $texto): array
    {
        preg_match_all('/@[a-zA-Z0-9_]+/', $texto, $coincidencias);

        return collect($coincidencias[0] ?? [])
            ->unique()
            ->values()
            ->all();
    }

    private function sanitizarHtml(string $html): string
    {
        $html = trim($html);
        $html = preg_replace('/^```(?:html)?\s*/iu', '', $html) ?? $html;
        $html = preg_replace('/\s*```$/u', '', $html) ?? $html;

        $permitidas = '<p><br><strong><b><em><i><u><ul><ol><li><blockquote><h2><h3><h4><table><thead><tbody><tr><th><td>';
        $html = strip_tags($html, $permitidas);

        $html = preg_replace('/\s+(?:style|class|id|onclick|onload|onerror|href|src)=("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $html) ?? $html;
        $html = preg_replace('/<p>\s*<\/p>/iu', '', $html) ?? $html;
        $html = preg_replace('/(?:<br\s*\/?>\s*){3,}/iu', '<br><br>', $html) ?? $html;

        return trim($html);
    }

    private function mensajeHttp(Response $response, string $predeterminado): string
    {
        $detalle = (string) (
            $response->json('error.message')
            ?? $response->json('message')
            ?? ''
        );

        return match ($response->status()) {
            400 => $detalle !== ''
            ? 'GroqCloud rechazó la solicitud: ' . $detalle
            : 'GroqCloud rechazó la solicitud por un formato no válido.',
            401 => 'La API key de GroqCloud es inválida o fue revocada.',
            403 => 'La cuenta de GroqCloud no tiene permiso para usar el modelo configurado.',
            404 => "El modelo {$this->model} no está disponible en GroqCloud.",
            429 => 'Se alcanzó temporalmente el límite de GroqCloud. Espera un momento y vuelve a intentarlo.',
            500, 502, 503, 504 => 'GroqCloud presenta una interrupción temporal. Vuelve a intentarlo más tarde.',
            default => $detalle !== ''
            ? $predeterminado . ' ' . $detalle
            : $predeterminado . ' Código HTTP: ' . $response->status() . '.',
        };
    }
}
