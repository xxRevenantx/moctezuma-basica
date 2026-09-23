<?php

namespace App\Support\Pdf;

use Dompdf\Dompdf;
use Dompdf\FontMetrics;
use RuntimeException;

/** Medidas en puntos, iguales a las utilizadas por el motor PDF. */
final class RegistroEscolaridadHeader
{
    private FontMetrics $metrics;
    private string $font;
    private string $family;
    private array $cache = [];

    public function __construct(Dompdf $dompdf, string $fontFile)
    {
        $this->metrics = $dompdf->getFontMetrics();
        $registered = is_readable($fontFile) && $this->metrics->registerFont([
            'family' => 'calibri', 'weight' => 'normal', 'style' => 'normal',
        ], $fontFile);
        // Medir y dibujar siempre con la misma fuente, incluso si falta Calibri.
        $this->family = $registered ? 'calibri' : 'Helvetica';
        $this->font = $this->metrics->getFont($this->family, 'normal');
    }

    public function fit(string $name): array
    {
        $text = mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $name)), 'UTF-8');
        if (isset($this->cache[$text])) {
            return $this->cache[$text];
        }

        // 100 px de alto y 50 px de ancho: caja girada de 67.5 x 33 pt.
        // Dejamos margen extra al medir para evitar redondeos junto al borde.
        $size = 7.5;
        for ($attempt = 0; $attempt < 100; $attempt++, $size *= 0.95) {
            $lines = $this->wrap($text, $size, 66.5);
            $lineHeight = max($size * 1.15, $this->metrics->getFontHeight($this->font, $size)) + 0.3;
            $height = count($lines) * $lineHeight;
            if ($height <= 31.5) {
                return $this->cache[$text] = [
                    'lines' => $lines,
                    'size' => $size,
                    'line_height' => $lineHeight,
                    'height' => $height,
                    'family' => $this->family,
                ];
            }
        }

        throw new RuntimeException('El nombre de la materia es demasiado largo para el encabezado del registro.');
    }

    private function wrap(string $text, float $size, float $width): array
    {
        $lines = [];
        $line = '';
        foreach (explode(' ', $text) as $word) {
            $candidate = $line === '' ? $word : $line . ' ' . $word;
            if ($this->metrics->getTextWidth($candidate, $this->font, $size) <= $width) {
                $line = $candidate;
                continue;
            }
            if ($line !== '') {
                $lines[] = $line;
                $line = '';
            }
            // También admite palabras sin espacios que superan el ancho.
            foreach (mb_str_split($word, 1, 'UTF-8') as $character) {
                if ($line !== '' && $this->metrics->getTextWidth($line . $character, $this->font, $size) > $width) {
                    $lines[] = $line;
                    $line = '';
                }
                $line .= $character;
            }
        }
        if ($line !== '' || $lines === []) {
            $lines[] = $line;
        }
        return $lines;
    }
}
