<?php

namespace App\Services;

use RuntimeException;
use Smalot\PdfParser\Parser;
use Symfony\Component\Process\Process;
use Illuminate\Support\Str;

class TutorDocumentOcrService
{
    public function __construct(
        private readonly Parser $pdfParser,
        private readonly TutorDocumentParser $documentParser
    ) {
    }

    /**
     * @return array{
     *   campos: array<string, ?string>, advertencias: array<int, string>,
     *   confianza: ?int, metodo: string, texto: string, capacidades: array<string, mixed>
     * }
     */
    public function analyze(string $path, string $documentType): array
    {
        if (! config('tutor_ocr.enabled', true)) {
            throw new RuntimeException('El lector local de documentos está desactivado en la configuración.');
        }

        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('El archivo temporal no existe o no puede leerse.');
        }

        $documentType = in_array($documentType, ['ine', 'curp'], true) ? $documentType : 'ine';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $capabilities = $this->capabilities();
        $temporaryDirectory = storage_path('app/tmp/tutor-ocr/' . Str::uuid());

        if (! is_dir($temporaryDirectory) && ! mkdir($temporaryDirectory, 0775, true) && ! is_dir($temporaryDirectory)) {
            throw new RuntimeException('No fue posible crear el directorio temporal del OCR.');
        }

        try {
            $text = '';
            $confidence = null;
            $method = '';
            $warnings = [];

            if ($extension === 'pdf') {
                $text = $this->extractPdfText($path);
                if (mb_strlen($text) >= (int) config('tutor_ocr.minimum_text_length', 35)) {
                    $method = 'Texto seleccionable del PDF';
                    $confidence = 100;
                } else {
                    if (! $capabilities['tesseract']) {
                        throw new RuntimeException(
                            'El PDF parece ser un escaneo y Tesseract no está instalado. Instala Tesseract o sube una fotografía JPG/PNG después de configurarlo.'
                        );
                    }
                    if (! $capabilities['pdftoppm']) {
                        throw new RuntimeException(
                            'El PDF es un escaneo. Para leerlo también se necesita Poppler (pdftoppm). Como alternativa, sube la página como imagen JPG o PNG.'
                        );
                    }

                    $images = $this->pdfToImages($path, $temporaryDirectory, $capabilities['pdftoppm_path']);
                    [$text, $confidence] = $this->ocrImages($images, $temporaryDirectory, $documentType, $capabilities);
                    $method = 'Tesseract OCR local sobre PDF escaneado';
                }
            } else {
                if (! $capabilities['tesseract']) {
                    throw new RuntimeException(
                        'Tesseract OCR no está instalado o no se encontró. Configura TESSERACT_BINARY en el archivo .env.'
                    );
                }

                [$text, $confidence] = $this->ocrImages([$path], $temporaryDirectory, $documentType, $capabilities);
                $method = 'Tesseract OCR local sobre imagen';
            }

            if (mb_strlen(trim($text)) < (int) config('tutor_ocr.minimum_text_length', 35)) {
                throw new RuntimeException(
                    'Se reconoció muy poco texto. Usa una imagen frontal, nítida, sin reflejos y con el documento ocupando la mayor parte de la fotografía.'
                );
            }

            $parsed = $this->documentParser->parse($text, $documentType);
            $warnings = [...$warnings, ...$parsed['advertencias']];

            if ($confidence !== null && $confidence < 55) {
                $warnings[] = "La lectura OCR obtuvo {$confidence}% de confianza. Comprueba cuidadosamente cada dato.";
            }

            return [
                'campos' => $parsed['campos'],
                'advertencias' => array_values(array_unique($warnings)),
                'confianza' => $confidence,
                'metodo' => $method,
                'texto' => config('tutor_ocr.show_raw_text', false) ? mb_substr($text, 0, 5000) : null,
                'capacidades' => $capabilities,
            ];
        } finally {
            $this->removeDirectory($temporaryDirectory);
        }
    }

    /** @return array<string, mixed> */
    public function capabilities(): array
    {
        $tesseract = $this->resolveBinary(
            config('tutor_ocr.tesseract_binary'),
            ['tesseract'],
            [
                'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
                'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
                'C:\\laragon\\bin\\tesseract\\tesseract.exe',
            ],
            ['--version']
        );

        $pdftoppm = $this->resolveBinary(
            config('tutor_ocr.pdftoppm_binary'),
            ['pdftoppm'],
            [
                'C:\\Program Files\\poppler\\Library\\bin\\pdftoppm.exe',
                'C:\\Program Files\\poppler\\bin\\pdftoppm.exe',
                'C:\\laragon\\bin\\poppler\\Library\\bin\\pdftoppm.exe',
                'C:\\laragon\\bin\\poppler\\bin\\pdftoppm.exe',
            ],
            ['-v']
        );

        $imageMagick = $this->resolveBinary(
            config('tutor_ocr.imagemagick_binary'),
            ['magick'],
            [
                'C:\\Program Files\\ImageMagick-7.1.1-Q16-HDRI\\magick.exe',
                'C:\\Program Files\\ImageMagick-7.1.1-Q16\\magick.exe',
                'C:\\laragon\\bin\\imagemagick\\magick.exe',
            ],
            ['-version']
        );

        return [
            'enabled' => (bool) config('tutor_ocr.enabled', true),
            'tesseract' => $tesseract !== null,
            'tesseract_path' => $tesseract,
            'pdftoppm' => $pdftoppm !== null,
            'pdftoppm_path' => $pdftoppm,
            'imagemagick' => $imageMagick !== null,
            'imagemagick_path' => $imageMagick,
            'pdf_text' => true,
        ];
    }

    private function extractPdfText(string $path): string
    {
        try {
            $pdf = $this->pdfParser->parseFile($path);
            return $this->normalizeText($pdf->getText());
        } catch (\Throwable) {
            return '';
        }
    }

    /** @return array<int, string> */
    private function pdfToImages(string $pdfPath, string $temporaryDirectory, string $binary): array
    {
        $prefix = $temporaryDirectory . DIRECTORY_SEPARATOR . 'pagina';
        $maxPages = max(1, min(5, (int) config('tutor_ocr.max_pages_pdf', 2)));

        $process = new Process([
            $binary,
            '-f', '1',
            '-l', (string) $maxPages,
            '-r', '300',
            '-png',
            $pdfPath,
            $prefix,
        ]);
        $process->setTimeout((int) config('tutor_ocr.timeout', 90));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('No fue posible convertir el PDF escaneado a imágenes: ' . trim($process->getErrorOutput()));
        }

        $images = glob($prefix . '-*.png') ?: [];
        sort($images, SORT_NATURAL);

        if ($images === []) {
            throw new RuntimeException('Poppler no generó imágenes a partir del PDF.');
        }

        return $images;
    }

    /** @return array{0: string, 1: ?int} */
    private function ocrImages(array $images, string $temporaryDirectory, string $documentType, array $capabilities): array
    {
        $texts = [];
        $confidences = [];

        foreach ($images as $index => $image) {
            $input = $this->preprocessImage($image, $temporaryDirectory, $index, $capabilities);
            $outputBase = $temporaryDirectory . DIRECTORY_SEPARATOR . 'ocr-' . $index;
            $psm = $documentType === 'ine' ? '11' : '6';

            $process = new Process([
                $capabilities['tesseract_path'],
                $input,
                $outputBase,
                '-l', (string) config('tutor_ocr.tesseract_language', 'spa+eng'),
                '--oem', '1',
                '--psm', $psm,
                'txt',
                'tsv',
            ]);
            $process->setTimeout((int) config('tutor_ocr.timeout', 90));
            $process->run();

            if (! $process->isSuccessful()) {
                $error = trim($process->getErrorOutput());
                if (str_contains(strtolower($error), 'failed loading language')) {
                    throw new RuntimeException(
                        'Tesseract está instalado, pero falta el idioma español (spa.traineddata). Instálalo o cambia TESSERACT_LANGUAGE=eng temporalmente.'
                    );
                }
                throw new RuntimeException('Tesseract no pudo analizar el documento: ' . $error);
            }

            $textPath = $outputBase . '.txt';
            if (is_file($textPath)) {
                $texts[] = $this->normalizeText((string) file_get_contents($textPath));
            }

            $tsvPath = $outputBase . '.tsv';
            if (is_file($tsvPath)) {
                $confidence = $this->confidenceFromTsv((string) file_get_contents($tsvPath));
                if ($confidence !== null) {
                    $confidences[] = $confidence;
                }
            }
        }

        return [
            trim(implode("\n", array_filter($texts))),
            $confidences !== [] ? (int) round(array_sum($confidences) / count($confidences)) : null,
        ];
    }

    private function preprocessImage(string $input, string $temporaryDirectory, int $index, array $capabilities): string
    {
        if (! ($capabilities['imagemagick'] ?? false)) {
            return $input;
        }

        $output = $temporaryDirectory . DIRECTORY_SEPARATOR . 'pre-' . $index . '.png';
        $process = new Process([
            $capabilities['imagemagick_path'],
            $input,
            '-auto-orient',
            '-resize', '2600x2600>',
            '-colorspace', 'Gray',
            '-contrast-stretch', '1%x1%',
            '-sharpen', '0x1',
            $output,
        ]);
        $process->setTimeout((int) config('tutor_ocr.timeout', 90));
        $process->run();

        return $process->isSuccessful() && is_file($output) ? $output : $input;
    }

    private function confidenceFromTsv(string $tsv): ?int
    {
        $weighted = 0.0;
        $weight = 0;
        $lines = preg_split('/\R/u', trim($tsv)) ?: [];

        foreach (array_slice($lines, 1) as $line) {
            $columns = explode("\t", $line);
            if (count($columns) < 12) {
                continue;
            }

            $confidence = (float) $columns[10];
            $word = trim($columns[11]);
            if ($confidence < 0 || $word === '') {
                continue;
            }

            $wordWeight = max(1, mb_strlen($word));
            $weighted += $confidence * $wordWeight;
            $weight += $wordWeight;
        }

        return $weight > 0 ? (int) round($weighted / $weight) : null;
    }

    private function resolveBinary(mixed $configured, array $commands, array $commonPaths, array $versionArguments = ['--version']): ?string
    {
        $candidates = [];
        if (is_string($configured) && trim($configured) !== '') {
            $candidates[] = trim($configured, " \t\n\r\0\x0B\"");
        }
        $candidates = [...$candidates, ...$commonPaths, ...$commands];

        foreach (array_unique($candidates) as $candidate) {
            if (str_contains($candidate, DIRECTORY_SEPARATOR) || preg_match('/^[A-Z]:\\\\/i', $candidate)) {
                if (! is_file($candidate)) {
                    continue;
                }
            }

            try {
                $process = new Process([$candidate, ...$versionArguments]);
                $process->setTimeout(4);
                $process->run();
                if ($process->isSuccessful()) {
                    return $candidate;
                }
            } catch (\Throwable) {
                // Continúa con el siguiente candidato.
            }
        }

        return null;
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\u{00A0}"], ["\n", "\n", ' '], $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
