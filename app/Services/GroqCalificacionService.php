<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GroqCalificacionService
{
    private string $baseUrl;
    private string $model;
    private ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('groq.base_url', 'https://api.groq.com/openai/v1'), '/');
        $this->model = (string) config(
            'groq.calificaciones.model',
            config('groq.model', 'openai/gpt-oss-20b')
        );
        $this->apiKey = config('groq.api_key');
    }

    public function model(): string
    {
        return $this->model;
    }

    /**
     * Genera un informe narrativo a partir de estadísticas grupales anónimas.
     *
     * @param array<string, mixed> $estadisticas
     * @return array{
     *     titulo: string,
     *     resumen_ejecutivo: string,
     *     diagnostico_pedagogico: string,
     *     fortalezas: array<int, string>,
     *     areas_atencion: array<int, string>,
     *     recomendaciones: array<int, string>,
     *     prioridad: string,
     *     aviso: string
     * }
     */
    public function generarDiagnostico(array $estadisticas, string $tipo = 'pedagogico'): array
    {
        $this->validarConfiguracion();

        $tiposPermitidos = [
            'pedagogico' => 'Informe pedagógico para personal docente',
            'direccion' => 'Resumen ejecutivo para dirección escolar',
            'consejo_tecnico' => 'Informe para sesión de consejo técnico escolar',
            'familias' => 'Resumen general y comprensible para madres, padres o tutores',
        ];

        if (!array_key_exists($tipo, $tiposPermitidos)) {
            throw new RuntimeException('El tipo de diagnóstico solicitado no es válido.');
        }

        $datos = $this->sanitizarDatos($estadisticas);

        $system = <<<'PROMPT'
Eres un asistente de análisis académico para una institución educativa mexicana.

Tu función es redactar un informe grupal usando exclusivamente estadísticas anónimas previamente calculadas por el sistema.

Reglas obligatorias:
- Escribe en español de México.
- No recalcules, alteres ni contradigas los datos recibidos.
- No inventes estudiantes, porcentajes, causas, conductas, diagnósticos ni circunstancias.
- No solicites ni menciones nombres, matrículas, CURP, teléfonos, correos o datos personales.
- No realices diagnósticos médicos, psicológicos o socioeconómicos.
- No atribuyas el resultado a falta de capacidad del alumnado o del personal docente.
- Distingue entre captura incompleta y bajo desempeño académico.
- Si hay datos pendientes, aclara que las conclusiones son preliminares.
- Formula recomendaciones concretas, respetuosas, realistas y verificables.
- El informe sirve como apoyo; las decisiones académicas corresponden al personal autorizado.
- Devuelve únicamente el objeto JSON solicitado por el esquema.
PROMPT;

        $prompt = 'Tipo de documento: ' . $tiposPermitidos[$tipo] . "\n\n";
        $prompt .= "Estadísticas grupales anónimas:\n";
        $prompt .= json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->asJson()
                ->connectTimeout((int) config('groq.connect_timeout', 8))
                ->timeout((int) config('groq.request_timeout', 60))
                ->retry(2, 800, throw: false)
                ->post($this->baseUrl . '/chat/completions', [
                    'model' => $this->model,
                    'temperature' => (float) config('groq.calificaciones.temperature', 0.25),
                    'max_tokens' => (int) config('groq.calificaciones.max_tokens', 1600),
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
                            'name' => 'diagnostico_academico_grupal',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'titulo' => ['type' => 'string'],
                                    'resumen_ejecutivo' => ['type' => 'string'],
                                    'diagnostico_pedagogico' => ['type' => 'string'],
                                    'fortalezas' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                    ],
                                    'areas_atencion' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                    ],
                                    'recomendaciones' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                    ],
                                    'prioridad' => [
                                        'type' => 'string',
                                        'enum' => ['baja', 'media', 'alta'],
                                    ],
                                    'aviso' => ['type' => 'string'],
                                ],
                                'required' => [
                                    'titulo',
                                    'resumen_ejecutivo',
                                    'diagnostico_pedagogico',
                                    'fortalezas',
                                    'areas_atencion',
                                    'recomendaciones',
                                    'prioridad',
                                    'aviso',
                                ],
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
            throw new RuntimeException(
                $this->mensajeHttp($response, 'GroqCloud no pudo generar el diagnóstico académico.')
            );
        }

        $contenido = trim((string) $response->json('choices.0.message.content', ''));

        if ($contenido === '') {
            throw new RuntimeException('GroqCloud no devolvió contenido para el diagnóstico.');
        }

        $resultado = json_decode($contenido, true);

        if (!is_array($resultado) || json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('GroqCloud devolvió una respuesta que no pudo interpretarse.');
        }

        return $this->normalizarResultado($resultado);
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

    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    private function sanitizarDatos(array $datos): array
    {
        $resultado = [];

        foreach ($datos as $clave => $valor) {
            $claveLimpia = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $clave) ?: 'dato';

            if (is_array($valor)) {
                $resultado[$claveLimpia] = array_is_list($valor)
                    ? $this->sanitizarLista($valor)
                    : $this->sanitizarDatos($valor);
                continue;
            }

            if (is_bool($valor) || is_int($valor) || is_float($valor) || $valor === null) {
                $resultado[$claveLimpia] = $valor;
                continue;
            }

            $resultado[$claveLimpia] = $this->limpiarTexto((string) $valor, 500);
        }

        return $resultado;
    }

    /**
     * @param array<mixed> $valores
     * @return array<mixed>
     */
    private function sanitizarLista(array $valores): array
    {
        $resultado = [];

        foreach (array_slice($valores, 0, 60) as $clave => $valor) {
            if (is_array($valor)) {
                $resultado[$clave] = $this->sanitizarDatos($valor);
                continue;
            }

            if (is_bool($valor) || is_int($valor) || is_float($valor) || $valor === null) {
                $resultado[$clave] = $valor;
                continue;
            }

            $resultado[$clave] = $this->limpiarTexto((string) $valor, 500);
        }

        return array_values($resultado);
    }

    private function limpiarTexto(string $texto, int $limite): string
    {
        $texto = html_entity_decode(strip_tags($texto), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('/\s+/u', ' ', trim($texto)) ?? '';

        $patrones = [
            '/\b[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d\b/iu',
            '/\b[\w.%+\-]+@[\w.\-]+\.[A-Z]{2,}\b/iu',
            '/\b(?:\+?52\s*)?(?:\d[\s\-()]*){10}\b/u',
        ];

        $texto = preg_replace($patrones, '[DATO OMITIDO]', $texto) ?? $texto;

        return mb_substr($texto, 0, $limite);
    }

    /**
     * @param array<string, mixed> $resultado
     * @return array{
     *     titulo: string,
     *     resumen_ejecutivo: string,
     *     diagnostico_pedagogico: string,
     *     fortalezas: array<int, string>,
     *     areas_atencion: array<int, string>,
     *     recomendaciones: array<int, string>,
     *     prioridad: string,
     *     aviso: string
     * }
     */
    private function normalizarResultado(array $resultado): array
    {
        $prioridad = mb_strtolower(trim((string) ($resultado['prioridad'] ?? 'media')));

        if (!in_array($prioridad, ['baja', 'media', 'alta'], true)) {
            $prioridad = 'media';
        }

        return [
            'titulo' => $this->textoRequerido($resultado['titulo'] ?? null, 'Diagnóstico académico grupal'),
            'resumen_ejecutivo' => $this->textoRequerido(
                $resultado['resumen_ejecutivo'] ?? null,
                'No se generó un resumen ejecutivo.'
            ),
            'diagnostico_pedagogico' => $this->textoRequerido(
                $resultado['diagnostico_pedagogico'] ?? null,
                'No se generó el diagnóstico pedagógico.'
            ),
            'fortalezas' => $this->normalizarLista($resultado['fortalezas'] ?? []),
            'areas_atencion' => $this->normalizarLista($resultado['areas_atencion'] ?? []),
            'recomendaciones' => $this->normalizarLista($resultado['recomendaciones'] ?? []),
            'prioridad' => $prioridad,
            'aviso' => $this->textoRequerido(
                $resultado['aviso'] ?? null,
                'Este informe es orientativo y debe ser revisado por personal autorizado.'
            ),
        ];
    }

    private function textoRequerido(mixed $valor, string $predeterminado): string
    {
        $texto = $this->limpiarTexto((string) $valor, 5000);

        return $texto !== '' ? $texto : $predeterminado;
    }

    /**
     * @return array<int, string>
     */
    private function normalizarLista(mixed $valores): array
    {
        if (!is_array($valores)) {
            return [];
        }

        return collect($valores)
            ->map(fn($valor) => $this->limpiarTexto((string) $valor, 1000))
            ->filter()
            ->take(8)
            ->values()
            ->all();
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
            : 'GroqCloud rechazó la solicitud. Revisa el modelo y la configuración.',
            401 => 'La API key de GroqCloud es inválida o fue revocada.',
            403 => 'La cuenta de GroqCloud no tiene permiso para usar el modelo configurado.',
            404 => "El modelo {$this->model} no está disponible en GroqCloud.",
            413 => 'La información enviada a GroqCloud excede el tamaño permitido.',
            429 => 'Se alcanzó temporalmente el límite de GroqCloud. Intenta nuevamente en unos momentos.',
            500, 502, 503, 504 => 'GroqCloud presenta una interrupción temporal. Intenta nuevamente más tarde.',
            default => $detalle !== ''
            ? $predeterminado . ' ' . $detalle
            : $predeterminado . ' Código HTTP: ' . $response->status() . '.',
        };
    }
}
