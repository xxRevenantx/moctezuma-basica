<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AzureDocumentIntelligence
{
    protected string $endpoint;
    protected string $key;

    public function __construct()
    {
        $this->endpoint = rtrim((string) config('services.azure.docintel.endpoint'), '/');
        $this->key = (string) config('services.azure.docintel.key');

        if ($this->endpoint === '' || $this->key === '') {
            throw new \RuntimeException(
                'Azure Document Intelligence no está configurado correctamente'
            );
        }
    }

    public function extractText(string $filePath): string
    {
        $url = "{$this->endpoint}/formrecognizer/documentModels/prebuilt-read:analyze?api-version=2023-07-31";

        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->key,
            'Content-Type' => 'application/pdf',
        ])->withBody(
                file_get_contents($filePath),
                'application/pdf'
            )->post($url);

        if (!$response->successful()) {
            throw new \RuntimeException($response->body());
        }

        // Azure responde async → obtenemos URL de resultado
        $operationUrl = $response->header('Operation-Location');

        return $this->pollResult($operationUrl);
    }

    protected function pollResult(string $url): string
    {
        for ($i = 0; $i < 30; $i++) {
            usleep(600000); // 0.6s

            $res = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->key,
            ])->get($url);

            if (!$res->successful()) {
                throw new \RuntimeException("Azure poll error: " . $res->body());
            }

            $json = $res->json();

            $status = $json['status'] ?? null;

            if ($status === 'succeeded') {
                return (string) data_get($json, 'analyzeResult.content', '');
            }

            if ($status === 'failed') {
                $msg = data_get($json, 'error.message')
                    ?: data_get($json, 'analyzeResult.errors.0.message')
                    ?: $res->body();

                throw new \RuntimeException("Azure OCR failed: " . $msg);
            }
        }

        throw new \RuntimeException('Timeout esperando resultado de Azure OCR');
    }

}
