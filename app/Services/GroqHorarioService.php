<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GroqHorarioService
{
    private string $baseUrl;
    private string $model;
    private ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('groq.base_url', 'https://api.groq.com/openai/v1'), '/');
        $this->model = (string) config(
            'groq.horarios.model',
            config('groq.model', 'openai/gpt-oss-20b')
        );
        $this->apiKey = config('groq.api_key');
    }

    public function model(): string
    {
        return $this->model;
    }

    /**
     * Explica un traslape detectado por Laravel. Las alternativas recibidas
     * ya fueron verificadas por el sistema y Groq solo las contextualiza.
     *
     * @param array<string, mixed> $datos
     * @return array{
     *     titulo: string,
     *     explicacion: string,
     *     riesgo_operativo: string,
     *     recomendacion_principal: string,
     *     criterios_decision: array<int, string>,
     *     pasos_sugeridos: array<int, string>,
     *     prioridad: string,
     *     aviso: string
     * }
     */
    public function explicarConflicto(array $datos): array
    {
        $this->validarConfiguracion();

        $system = <<<'PROMPT'
Eres un asistente administrativo especializado en organización de horarios escolares en México.

Laravel ya detectó un traslape real de docente y calculó alternativas que están libres tanto para el grupo como para el docente. Tu tarea es explicar el conflicto y orientar la decisión.

Reglas obligatorias:
- Escribe en español de México, de forma clara, breve y profesional.
- Usa exclusivamente los datos recibidos.
- No inventes días, horas, grupos, materias, docentes ni restricciones.
- No recalcules ni declares válida una alternativa que no aparezca en alternativas_validas.
- No menciones nombres de docentes ni solicites datos personales.
- Explica que un docente no puede atender dos actividades superpuestas.
- Prioriza las alternativas verificadas por Laravel.
- Solo menciona guardar con traslape como última opción y advierte que requiere una decisión administrativa expresa.
- No modifiques ni guardes información.
- Devuelve únicamente el objeto JSON solicitado por el esquema.
PROMPT;

        return $this->solicitarJson(
            system: $system,
            user: "Conflicto y alternativas verificadas por el sistema:\n" . $this->json($datos),
            schemaName: 'explicacion_conflicto_horario',
            schema: [
                'type' => 'object',
                'properties' => [
                    'titulo' => ['type' => 'string'],
                    'explicacion' => ['type' => 'string'],
                    'riesgo_operativo' => ['type' => 'string'],
                    'recomendacion_principal' => ['type' => 'string'],
                    'criterios_decision' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'pasos_sugeridos' => [
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
                    'explicacion',
                    'riesgo_operativo',
                    'recomendacion_principal',
                    'criterios_decision',
                    'pasos_sugeridos',
                    'prioridad',
                    'aviso',
                ],
                'additionalProperties' => false,
            ],
            maxTokens: (int) config('groq.horarios.conflicto_max_tokens', 1200)
        );
    }

    /**
     * Redacta un análisis general usando el diagnóstico determinístico del horario.
     *
     * @param array<string, mixed> $datos
     * @return array{
     *     titulo: string,
     *     resumen: string,
     *     hallazgos: array<int, string>,
     *     sugerencias: array<int, string>,
     *     orden_revision: array<int, string>,
     *     equilibrio_carga: string,
     *     prioridad: string,
     *     aviso: string
     * }
     */
    public function analizarHorario(array $datos, string $tipo = 'operativo'): array
    {
        $this->validarConfiguracion();

        $tipos = [
            'operativo' => 'Análisis operativo para quien construye el horario',
            'direccion' => 'Resumen ejecutivo para dirección escolar',
            'consejo_tecnico' => 'Informe breve para consejo técnico escolar',
        ];

        if (!array_key_exists($tipo, $tipos)) {
            throw new RuntimeException('El tipo de análisis de horario no es válido.');
        }

        $system = <<<'PROMPT'
Eres un asistente administrativo especializado en organización de horarios escolares en México.

Recibirás un diagnóstico ya calculado por Laravel. Tu tarea es explicarlo y proponer un orden práctico de revisión.

Reglas obligatorias:
- Escribe en español de México con tono profesional y directo.
- Usa exclusivamente la información recibida.
- No cambies cálculos, porcentajes, conteos ni estados.
- No inventes conflictos, disponibilidad, docentes, materias o causas.
- No propongas días u horas concretos si Laravel no proporcionó alternativas verificadas.
- Distingue entre celdas pendientes, materias sin colocar, falta de profesor y distribución desigual.
- No menciones nombres de docentes ni datos personales.
- Las sugerencias deben ser concretas y aplicables dentro del sistema.
- No guardes ni modifiques el horario.
- La decisión final corresponde al personal autorizado.
- Devuelve únicamente el objeto JSON solicitado por el esquema.
PROMPT;

        return $this->solicitarJson(
            system: $system,
            user: 'Tipo de análisis: ' . $tipos[$tipo] . "\n\nDiagnóstico calculado por Laravel:\n" . $this->json($datos),
            schemaName: 'analisis_general_horario',
            schema: [
                'type' => 'object',
                'properties' => [
                    'titulo' => ['type' => 'string'],
                    'resumen' => ['type' => 'string'],
                    'hallazgos' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'sugerencias' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'orden_revision' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'equilibrio_carga' => ['type' => 'string'],
                    'prioridad' => [
                        'type' => 'string',
                        'enum' => ['baja', 'media', 'alta'],
                    ],
                    'aviso' => ['type' => 'string'],
                ],
                'required' => [
                    'titulo',
                    'resumen',
                    'hallazgos',
                    'sugerencias',
                    'orden_revision',
                    'equilibrio_carga',
                    'prioridad',
                    'aviso',
                ],
                'additionalProperties' => false,
            ],
            maxTokens: (int) config('groq.horarios.analisis_max_tokens', 1600)
        );
    }

    /**
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function solicitarJson(
        string $system,
        string $user,
        string $schemaName,
        array $schema,
        int $maxTokens
    ): array {
        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->asJson()
                ->connectTimeout((int) config('groq.connect_timeout', 8))
                ->timeout((int) config('groq.request_timeout', 60))
                ->retry(2, 800, throw: false)
                ->post($this->baseUrl . '/chat/completions', [
                    'model' => $this->model,
                    'temperature' => (float) config('groq.horarios.temperature', 0.2),
                    'max_tokens' => $maxTokens,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => $schemaName,
                            'strict' => true,
                            'schema' => $schema,
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
                $this->mensajeHttp($response, 'GroqCloud no pudo analizar el horario.')
            );
        }

        $contenido = trim((string) $response->json('choices.0.message.content', ''));

        if ($contenido === '') {
            throw new RuntimeException('GroqCloud no devolvió contenido para el análisis del horario.');
        }

        $resultado = json_decode($contenido, true);

        if (!is_array($resultado) || json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('GroqCloud devolvió una respuesta que no pudo interpretarse.');
        }

        return $this->normalizar($resultado);
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
     * @param array<string, mixed> $resultado
     * @return array<string, mixed>
     */
    private function normalizar(array $resultado): array
    {
        foreach ($resultado as $clave => $valor) {
            if (is_string($valor)) {
                $resultado[$clave] = trim(strip_tags($valor));
                continue;
            }

            if (is_array($valor)) {
                $resultado[$clave] = collect($valor)
                    ->map(fn($item) => is_string($item) ? trim(strip_tags($item)) : $item)
                    ->filter(fn($item) => $item !== '' && $item !== null)
                    ->values()
                    ->all();
            }
        }

        return $resultado;
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function json(array $datos): string
    {
        return (string) json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }

    private function mensajeHttp(Response $response, string $predeterminado): string
    {
        $mensaje = trim((string) $response->json('error.message', ''));

        if ($response->status() === 401) {
            return 'La clave GROQ_API_KEY no es válida o no tiene acceso.';
        }

        if ($response->status() === 429) {
            return 'GroqCloud alcanzó temporalmente el límite de solicitudes. Intenta nuevamente en unos segundos.';
        }

        if ($response->serverError()) {
            return 'GroqCloud presentó un error temporal. Intenta nuevamente más tarde.';
        }

        return $mensaje !== '' ? $mensaje : $predeterminado;
    }
}
