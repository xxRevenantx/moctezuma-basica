<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GroqDashboardService
{
    private string $baseUrl;
    private string $model;
    private ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('groq.base_url', 'https://api.groq.com/openai/v1'), '/');
        $this->model = (string) config(
            'groq.dashboard.model',
            config('groq.model', 'openai/gpt-oss-20b')
        );
        $this->apiKey = config('groq.api_key');
    }

    public function model(): string
    {
        return $this->model;
    }

    /**
     * Redacta un resumen administrativo usando métricas calculadas por Laravel.
     *
     * @param array<string, mixed> $datos
     * @return array{
     *     titulo: string,
     *     estado_general: string,
     *     resumen_ejecutivo: string,
     *     prioridades: array<int, array{orden: int, asunto: string, motivo: string, accion: string, plazo: string}>,
     *     hallazgos: array<int, string>,
     *     acciones_recomendadas: array<int, string>,
     *     proximos_vencimientos: array<int, string>,
     *     observaciones_nivel: array<int, array{nivel: string, resumen: string}>,
     *     aviso: string
     * }
     */
    public function generarResumen(array $datos, string $tipo = 'ejecutivo'): array
    {
        $this->validarConfiguracion();

        $tipos = [
            'ejecutivo' => 'Resumen ejecutivo para dirección escolar',
            'operativo' => 'Resumen operativo para control escolar y administración',
            'semanal' => 'Plan breve de prioridades para la semana',
        ];

        if (!array_key_exists($tipo, $tipos)) {
            throw new RuntimeException('El tipo de resumen administrativo no es válido.');
        }

        $system = <<<'PROMPT'
Eres un asistente administrativo para una institución educativa mexicana.

Recibirás métricas y alertas calculadas previamente por Laravel. Tu única función es redactar un resumen claro, priorizado y práctico.

Reglas obligatorias:
- Escribe en español de México con tono profesional, directo y comprensible.
- Usa exclusivamente la información recibida.
- No recalcules, alteres ni contradigas cantidades, porcentajes, fechas o estados.
- Conserva exactamente el valor recibido en estado_calculado.clave como estado_general.
- No inventes causas, responsables, alumnos, docentes, grupos, fechas o problemas.
- No solicites ni menciones nombres, matrículas, CURP, correos, teléfonos o datos personales.
- No hagas diagnósticos médicos, psicológicos, legales o financieros.
- Distingue pendientes administrativos de resultados académicos.
- Prioriza primero: grupos sin horario, materias sin docente y alumnos sin grupo.
- Después considera documentos pendientes y periodos próximos a cerrar.
- Si no existen pendientes, indícalo sin crear alertas artificiales.
- Las acciones deben ser realizables dentro de los módulos descritos por los datos.
- No afirmes que una tarea fue resuelta; solo propone revisión o seguimiento.
- Los plazos deben ser cualitativos, como “hoy”, “esta semana” o “antes del cierre”, salvo que exista una fecha explícita.
- No modifiques ni guardes registros.
- Devuelve únicamente el objeto JSON solicitado por el esquema.
PROMPT;

        $user = 'Formato solicitado: ' . $tipos[$tipo] . "\n\n";
        $user .= "Datos administrativos anónimos calculados por Laravel:\n";
        $user .= $this->json($datos);

        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->asJson()
                ->connectTimeout((int) config('groq.connect_timeout', 8))
                ->timeout((int) config('groq.request_timeout', 60))
                ->retry(2, 800, throw: false)
                ->post($this->baseUrl . '/chat/completions', [
                    'model' => $this->model,
                    'temperature' => (float) config('groq.dashboard.temperature', 0.20),
                    'max_tokens' => (int) config('groq.dashboard.max_tokens', 1900),
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'resumen_administrativo_dashboard',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'titulo' => ['type' => 'string'],
                                    'estado_general' => [
                                        'type' => 'string',
                                        'enum' => ['estable', 'atencion', 'prioritario'],
                                    ],
                                    'resumen_ejecutivo' => ['type' => 'string'],
                                    'prioridades' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'orden' => ['type' => 'integer'],
                                                'asunto' => ['type' => 'string'],
                                                'motivo' => ['type' => 'string'],
                                                'accion' => ['type' => 'string'],
                                                'plazo' => ['type' => 'string'],
                                            ],
                                            'required' => ['orden', 'asunto', 'motivo', 'accion', 'plazo'],
                                            'additionalProperties' => false,
                                        ],
                                    ],
                                    'hallazgos' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                    ],
                                    'acciones_recomendadas' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                    ],
                                    'proximos_vencimientos' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                    ],
                                    'observaciones_nivel' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'nivel' => ['type' => 'string'],
                                                'resumen' => ['type' => 'string'],
                                            ],
                                            'required' => ['nivel', 'resumen'],
                                            'additionalProperties' => false,
                                        ],
                                    ],
                                    'aviso' => ['type' => 'string'],
                                ],
                                'required' => [
                                    'titulo',
                                    'estado_general',
                                    'resumen_ejecutivo',
                                    'prioridades',
                                    'hallazgos',
                                    'acciones_recomendadas',
                                    'proximos_vencimientos',
                                    'observaciones_nivel',
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
                $this->mensajeHttp($response, 'GroqCloud no pudo generar el resumen administrativo.')
            );
        }

        $contenido = trim((string) $response->json('choices.0.message.content', ''));

        if ($contenido === '') {
            throw new RuntimeException('GroqCloud no devolvió contenido para el resumen administrativo.');
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
        $limpiar = function (mixed $valor) use (&$limpiar): mixed {
            if (is_string($valor)) {
                return trim(strip_tags($valor));
            }

            if (is_array($valor)) {
                $normalizado = [];

                foreach ($valor as $clave => $item) {
                    $item = $limpiar($item);

                    if ($item === '' || $item === null || $item === []) {
                        continue;
                    }

                    $normalizado[$clave] = $item;
                }

                return array_is_list($valor)
                    ? array_values($normalizado)
                    : $normalizado;
            }

            return $valor;
        };

        return $limpiar($resultado);
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function json(array $datos): string
    {
        return (string) json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_PRETTY_PRINT
            | JSON_INVALID_UTF8_SUBSTITUTE
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

        if ($response->status() === 400 && $mensaje !== '') {
            return 'GroqCloud rechazó la solicitud: ' . $mensaje;
        }

        return $mensaje !== '' ? $mensaje : $predeterminado;
    }
}
