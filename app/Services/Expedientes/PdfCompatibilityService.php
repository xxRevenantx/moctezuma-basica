<?php

namespace App\Services\Expedientes;

use App\Exceptions\Expedientes\PdfCompatibilityException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use Symfony\Component\Process\Process;
use Throwable;

class PdfCompatibilityService
{
    /**
     * Devuelve una copia procesable por FPDI. Si el PDF original ya es
     * compatible, la ruta de salida será la misma ruta recibida.
     *
     * @return array{
     *   path:string,
     *   pages:int,
     *   status:string,
     *   normalizer:?string,
     *   original_compatible:bool,
     *   diagnostics:array<int, array<string, mixed>>
     * }
     */
    public function prepare(string $path): array
    {
        $this->assertLibrariesAvailable();
        $this->assertPdfSignature($path);

        try {
            return [
                'path' => $path,
                'pages' => $this->countPages($path),
                'status' => 'original_compatible',
                'normalizer' => null,
                'original_compatible' => true,
                'diagnostics' => [],
            ];
        } catch (Throwable $originalError) {
            if ($this->looksEncrypted($path)) {
                throw new PdfCompatibilityException(
                    'encrypted',
                    'El PDF está cifrado o protegido con contraseña. Guarda una copia sin contraseña y vuelve a seleccionarla.',
                    false,
                    ['parser_error' => $originalError->getMessage()],
                    $originalError,
                );
            }

            if (! (bool) config('expedientes_organizador.pdf_normalization.enabled', true)) {
                throw new PdfCompatibilityException(
                    'parser_incompatible',
                    'El PDF es visible, pero su estructura no es compatible con el organizador de páginas.',
                    true,
                    ['parser_error' => $originalError->getMessage()],
                    $originalError,
                );
            }

            $diagnostics = [];
            $normalizers = $this->normalizers();

            foreach ($normalizers as $normalizer) {
                $output = $this->temporaryOutputPath();

                try {
                    $result = $this->runNormalizer($normalizer, $path, $output);
                    $diagnostics[] = $result;

                    if (! $result['success'] || ! File::exists($output) || File::size($output) < 8) {
                        File::delete($output);
                        continue;
                    }

                    try {
                        $pages = $this->countPages($output);
                    } catch (Throwable $normalizedError) {
                        $diagnostics[] = [
                            'normalizer' => $normalizer['name'],
                            'success' => false,
                            'stage' => 'fpdi_validation',
                            'message' => $normalizedError->getMessage(),
                        ];
                        File::delete($output);
                        continue;
                    }

                    return [
                        'path' => $output,
                        'pages' => $pages,
                        'status' => 'normalized',
                        'normalizer' => $normalizer['name'],
                        'original_compatible' => false,
                        'diagnostics' => $diagnostics,
                    ];
                } catch (Throwable $normalizerError) {
                    $diagnostics[] = [
                        'normalizer' => $normalizer['name'],
                        'success' => false,
                        'stage' => 'execution',
                        'message' => $normalizerError->getMessage(),
                    ];
                    File::delete($output);
                }
            }

            throw new PdfCompatibilityException(
                'parser_incompatible',
                $normalizers === []
                    ? 'El PDF es visible, pero FPDI no puede organizarlo y el servidor no tiene qpdf o Ghostscript disponible. Puedes conservar el archivo original sin organizar.'
                    : 'El PDF es visible, pero no fue posible normalizarlo para organizar sus páginas. Puedes conservar el archivo original sin organizar.',
                true,
                [
                    'parser_error' => $originalError->getMessage(),
                    'normalizers' => $diagnostics,
                ],
                $originalError,
            );
        }
    }

    /**
     * Verifica las mismas dependencias PDF utilizadas por Moctezuma
     * Licenciaturas. Sin FPDF y FPDI cualquier archivo terminaría siendo
     * interpretado erróneamente como incompatible.
     */
    public function assertLibrariesAvailable(): void
    {
        if (! class_exists(\FPDF::class)) {
            throw ValidationException::withMessages([
                'archivo' => 'Falta la librería setasign/fpdf requerida para procesar documentos. Ejecuta: composer require setasign/fpdf:^1.8 setasign/fpdi:^2.6',
            ]);
        }

        if (! class_exists(Fpdi::class)) {
            throw ValidationException::withMessages([
                'archivo' => 'Falta la librería setasign/fpdi requerida para leer y organizar páginas PDF. Ejecuta: composer require setasign/fpdf:^1.8 setasign/fpdi:^2.6',
            ]);
        }
    }

    public function estimatePages(string $path): int
    {
        if (! File::exists($path)) {
            return 1;
        }

        $contents = @File::get($path);
        if (! is_string($contents) || $contents === '') {
            return 1;
        }

        $matches = preg_match_all('/\/Type\s*\/Page\b/', $contents);

        return max((int) $matches, 1);
    }

    protected function countPages(string $path): int
    {
        $fpdi = new Fpdi();
        $pages = (int) $fpdi->setSourceFile($path);

        if ($pages < 1) {
            throw new RuntimeException('El PDF no contiene páginas utilizables.');
        }

        return $pages;
    }

    protected function assertPdfSignature(string $path): void
    {
        if (! File::exists($path) || File::size($path) < 8) {
            throw new PdfCompatibilityException(
                'damaged',
                'El archivo está vacío, incompleto o no pudo leerse como PDF.',
                false,
            );
        }

        $handle = @fopen($path, 'rb');
        if (! is_resource($handle)) {
            throw new PdfCompatibilityException(
                'unreadable',
                'No fue posible abrir el archivo PDF para validarlo.',
                false,
            );
        }

        try {
            $signature = (string) fread($handle, 8);
        } finally {
            fclose($handle);
        }

        if (! str_starts_with(ltrim($signature, "\xEF\xBB\xBF\x00\x09\x0A\x0D\x20"), '%PDF-')) {
            throw new PdfCompatibilityException(
                'invalid_signature',
                'El archivo seleccionado no contiene una firma PDF válida.',
                false,
            );
        }
    }

    protected function looksEncrypted(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if (! is_resource($handle)) {
            return false;
        }

        $carry = '';

        try {
            while (! feof($handle)) {
                $chunk = fread($handle, 1024 * 1024);
                if (! is_string($chunk) || $chunk === '') {
                    break;
                }

                $haystack = $carry . $chunk;
                if (preg_match('/\/Encrypt\b/', $haystack) === 1) {
                    return true;
                }

                $carry = substr($haystack, -32);
            }
        } finally {
            fclose($handle);
        }

        return false;
    }

    /** @return array<int, array{name:string,binary:string}> */
    protected function normalizers(): array
    {
        $normalizers = [];

        $qpdf = $this->resolveBinary(
            (string) config('expedientes_organizador.pdf_normalization.qpdf_binary', ''),
            ['qpdf', 'qpdf.exe'],
            $this->windowsGlobs([
                'C:/Program Files/qpdf*/bin/qpdf.exe',
                'C:/Program Files (x86)/qpdf*/bin/qpdf.exe',
                'C:/tools/qpdf/bin/qpdf.exe',
            ])
        );

        if ($qpdf !== null) {
            $normalizers[] = ['name' => 'qpdf', 'binary' => $qpdf];
        }

        $ghostscript = $this->resolveBinary(
            (string) config('expedientes_organizador.pdf_normalization.ghostscript_binary', ''),
            PHP_OS_FAMILY === 'Windows'
                ? ['gswin64c', 'gswin64c.exe', 'gswin32c', 'gswin32c.exe']
                : ['gs'],
            $this->windowsGlobs([
                'C:/Program Files/gs/gs*/bin/gswin64c.exe',
                'C:/Program Files/gs/gs*/bin/gswin32c.exe',
                'C:/Program Files (x86)/gs/gs*/bin/gswin32c.exe',
            ])
        );

        if ($ghostscript !== null) {
            $normalizers[] = ['name' => 'ghostscript', 'binary' => $ghostscript];
        }

        return $normalizers;
    }

    /** @param array<int, string> $pathCandidates */
    protected function resolveBinary(string $configured, array $commandCandidates, array $pathCandidates): ?string
    {
        $candidates = array_values(array_unique(array_filter([
            trim($configured),
            ...$pathCandidates,
            ...$commandCandidates,
        ])));

        foreach ($candidates as $candidate) {
            if ($this->binaryWorks($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function binaryWorks(string $binary): bool
    {
        if ($this->looksLikePath($binary) && ! File::isFile($binary)) {
            return false;
        }

        try {
            $process = new Process([$binary, '--version']);
            $process->setTimeout(5);
            $process->run();

            return $process->isSuccessful();
        } catch (Throwable) {
            return false;
        }
    }

    /** @return array<int, string> */
    protected function windowsGlobs(array $patterns): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return [];
        }

        $paths = [];
        foreach ($patterns as $pattern) {
            foreach (glob($pattern) ?: [] as $path) {
                $paths[] = str_replace('/', DIRECTORY_SEPARATOR, $path);
            }
        }

        usort($paths, static fn (string $a, string $b): int => strnatcasecmp($b, $a));

        return $paths;
    }

    protected function looksLikePath(string $binary): bool
    {
        return str_contains($binary, '/') || str_contains($binary, '\\');
    }

    /**
     * @param array{name:string,binary:string} $normalizer
     * @return array<string, mixed>
     */
    protected function runNormalizer(array $normalizer, string $input, string $output): array
    {
        $timeout = max((int) config('expedientes_organizador.pdf_normalization.timeout_seconds', 45), 5);

        $command = match ($normalizer['name']) {
            'qpdf' => [
                $normalizer['binary'],
                '--object-streams=disable',
                '--stream-data=compress',
                '--recompress-flate',
                '--linearize',
                $input,
                $output,
            ],
            'ghostscript' => [
                $normalizer['binary'],
                '-dSAFER',
                '-dBATCH',
                '-dNOPAUSE',
                '-dQUIET',
                '-dPDFSTOPONERROR',
                '-sDEVICE=pdfwrite',
                '-dCompatibilityLevel=1.4',
                '-dDetectDuplicateImages=true',
                '-dCompressFonts=true',
                '-sOutputFile=' . $output,
                $input,
            ],
            default => throw new RuntimeException('Normalizador PDF desconocido.'),
        };

        $process = new Process($command);
        $process->setTimeout($timeout);
        $process->run();

        $exitCode = $process->getExitCode();
        $success = $process->isSuccessful()
            || ($normalizer['name'] === 'qpdf' && $exitCode === 3 && File::exists($output));

        return [
            'normalizer' => $normalizer['name'],
            'success' => $success,
            'exit_code' => $exitCode,
            'message' => Str::limit(trim($process->getErrorOutput() ?: $process->getOutput()), 1500, ''),
        ];
    }

    protected function temporaryOutputPath(): string
    {
        $directory = storage_path('app/temp/expedientes-organizador/normalizados');
        File::ensureDirectoryExists($directory);

        return $directory . DIRECTORY_SEPARATOR . Str::uuid() . '.pdf';
    }
}
