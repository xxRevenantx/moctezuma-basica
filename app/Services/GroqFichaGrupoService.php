<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GroqFichaGrupoService
{
    private string $baseUrl;
    private string $model;
    private ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('groq.base_url', 'https://api.groq.com/openai/v1'), '/');
        $this->model = (string) config(
            'groq.fichas_grupo.model',
            config('groq.model', 'openai/gpt-oss-20b')
        );
        $this->apiKey = config('groq.api_key');
    }

    public function model(): string
    {
        return $this->model;
    }

    /**
     * Genera un informe descriptivo grupal usando fragmentos anonimizados
     * de las fichas del grado, grupo y periodo seleccionados.
     *
     * @param array<string, mixed> $datos
     * @return array{
     *     titulo: string,
     *     descripcion_general: string,
     *     sintesis_campos: array<int, array{
     *         campo: string,
     *         sintesis: string,
     *         fortalezas: array<int, string>,
     *         aspectos_por_fortalecer: array<int, string>
     *     }>,
     *     fortalezas_grupales: array<int, string>,
     *     areas_acompanamiento: array<int, string>,
     *     recomendaciones_grupales: array<int, string>,
     *     estrategias_docentes: array<int, string>,
     *     prioridad_seguimiento: string,
     *     aviso: string
     * }
     */
    public function generarInforme(array $datos, string $tipo = 'pedagogico'): array
    {
        $this->validarConfiguracion();

        $tiposPermitidos = [
            'pedagogico' => 'Informe pedagógico para la educadora o el educador del grupo',
            'direccion' => 'Resumen ejecutivo para dirección escolar',
            'consejo_tecnico' => 'Informe para sesión de consejo técnico escolar',
            'familias' => 'Descripción general comprensible para madres, padres o tutores',
        ];

        if (!array_key_exists($tipo, $tiposPermitidos)) {
            throw new RuntimeException('El tipo de informe grupal solicitado no es válido.');
        }

        $datosSanitizados = $this->sanitizarDatos($datos);

        $system = <<<'PROMPT'
Eres un asistente pedagógico especializado en educación preescolar en México.

Tu tarea es redactar un informe descriptivo general de un grado y grupo a partir de fichas individuales ya anonimizadas por el sistema.

Reglas obligatorias:
- Escribe en español de México, con tono profesional, respetuoso, claro y pedagógico.
- Usa exclusivamente la información recibida.
- No inventes aprendizajes, conductas, causas, porcentajes, necesidades ni circunstancias.
- No menciones nombres, matrículas, CURP, teléfonos, correos ni datos personales.
- No individualices casos ni uses expresiones como "un alumno", "una niña" o "cierto estudiante" para señalar situaciones particulares.
- No realices diagnósticos médicos, psicológicos, del neurodesarrollo, familiares o socioeconómicos.
- No etiquetes al grupo con expresiones negativas.
- Describe tendencias comunes del grupo, no casos aislados.
- Distingue claramente entre información faltante y aspectos pedagógicos por acompañar.
- Si la cobertura de fichas es baja o parcial, indica que el informe es preliminar.
- En el apartado para familias evita tecnicismos y redacta recomendaciones aplicables en casa.
- Para dirección o consejo técnico, prioriza hallazgos grupales y acciones verificables.
- Las estrategias deben ser realistas para preescolar, observables y vinculadas con los campos formativos.
- No sustituyas la valoración profesional de la educadora o el educador.
- Devuelve únicamente el objeto JSON solicitado por el esquema.
PROMPT;

        $prompt = 'Tipo de documento: ' . $tiposPermitidos[$tipo] . "\n\n";
        $prompt .= "Información grupal anonimizada:\n";
        $prompt .= json_encode(
            $datosSanitizados,
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
                    'temperature' => (float) config('groq.fichas_grupo.temperature', 0.25),
                    'max_tokens' => (int) config('groq.fichas_grupo.max_tokens', 2200),
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'informe_descriptivo_grupal_preescolar',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'titulo' => ['type' => 'string'],
                                    'descripcion_general' => ['type' => 'string'],
                                    'sintesis_campos' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'campo' => ['type' => 'string'],
                                                'sintesis' => ['type' => 'string'],
                                                'fortalezas' => [
                                                    'type' => 'array',
                                                    'items' => ['type' => 'string'],
                                                ],
                                                'aspectos_por_fortalecer' => [
                                                    'type' => 'array',
                                                    'items' => ['type' => 'string'],
                                                ],
                                            ],
                                            'required' => [
                                                'campo',
                                                'sintesis',
                                                'fortalezas',
                                                'aspectos_por_fortalecer',
                                            ],
                                            'additionalProperties' => false,
                                        ],
                                    ],
                                    'fortalezas_grupales' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                    ],
                                    'areas_acompanamiento' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                    ],
                                    'recomendaciones_grupales' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                    ],
                                    'estrategias_docentes' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                    ],
                                    'prioridad_seguimiento' => [
                                        'type' => 'string',
                                        'enum' => ['baja', 'media', 'alta'],
                                    ],
                                    'aviso' => ['type' => 'string'],
                                ],
                                'required' => [
                                    'titulo',
                                    'descripcion_general',
                                    'sintesis_campos',
                                    'fortalezas_grupales',
                                    'areas_acompanamiento',
                                    'recomendaciones_grupales',
                                    'estrategias_docentes',
                                    'prioridad_seguimiento',
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
                $this->mensajeHttp($response, 'GroqCloud no pudo generar el informe descriptivo grupal.')
            );
        }

        $contenido = trim((string) $response->json('choices.0.message.content', ''));

        if ($contenido === '') {
            throw new RuntimeException('GroqCloud no devolvió contenido para el informe grupal.');
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

            $resultado[$claveLimpia] = $this->limpiarTexto((string) $valor, 1000);
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

        foreach (array_slice($valores, 0, 150) as $clave => $valor) {
            if (is_array($valor)) {
                $resultado[$clave] = array_is_list($valor)
                    ? $this->sanitizarLista($valor)
                    : $this->sanitizarDatos($valor);
                continue;
            }

            if (is_bool($valor) || is_int($valor) || is_float($valor) || $valor === null) {
                $resultado[$clave] = $valor;
                continue;
            }

            $resultado[$clave] = $this->limpiarTexto((string) $valor, 1000);
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
     * @return array<string, mixed>
     */
    private function normalizarResultado(array $resultado): array
    {
        $prioridad = mb_strtolower(trim((string) ($resultado['prioridad_seguimiento'] ?? 'media')));

        if (!in_array($prioridad, ['baja', 'media', 'alta'], true)) {
            $prioridad = 'media';
        }

        return [
            'titulo' => $this->textoRequerido(
                $resultado['titulo'] ?? null,
                'Informe descriptivo grupal de preescolar'
            ),
            'descripcion_general' => $this->textoRequerido(
                $resultado['descripcion_general'] ?? null,
                'No se generó una descripción general del grupo.'
            ),
            'sintesis_campos' => $this->normalizarSintesisCampos($resultado['sintesis_campos'] ?? []),
            'fortalezas_grupales' => $this->normalizarLista($resultado['fortalezas_grupales'] ?? []),
            'areas_acompanamiento' => $this->normalizarLista($resultado['areas_acompanamiento'] ?? []),
            'recomendaciones_grupales' => $this->normalizarLista($resultado['recomendaciones_grupales'] ?? []),
            'estrategias_docentes' => $this->normalizarLista($resultado['estrategias_docentes'] ?? []),
            'prioridad_seguimiento' => $prioridad,
            'aviso' => $this->textoRequerido(
                $resultado['aviso'] ?? null,
                'Este informe es orientativo y debe ser revisado por la educadora o el educador responsable.'
            ),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function normalizarSintesisCampos(mixed $items): array
    {
        if (!is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn($item) => is_array($item))
            ->map(fn(array $item) => [
                'campo' => $this->textoRequerido($item['campo'] ?? null, 'Campo formativo'),
                'sintesis' => $this->textoRequerido($item['sintesis'] ?? null, 'Sin síntesis disponible.'),
                'fortalezas' => $this->normalizarLista($item['fortalezas'] ?? []),
                'aspectos_por_fortalecer' => $this->normalizarLista($item['aspectos_por_fortalecer'] ?? []),
            ])
            ->take(8)
            ->values()
            ->all();
    }

    private function textoRequerido(mixed $valor, string $predeterminado): string
    {
        $texto = $this->limpiarTexto((string) $valor, 6000);

        return $texto !== '' ? $texto : $predeterminado;
    }

    /** @return array<int, string> */
    private function normalizarLista(mixed $valores): array
    {
        if (!is_array($valores)) {
            return [];
        }

        return collect($valores)
            ->map(fn($valor) => $this->limpiarTexto((string) $valor, 1200))
            ->filter()
            ->take(10)
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
