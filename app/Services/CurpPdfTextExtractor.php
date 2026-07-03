<?php

namespace App\Services;

use RuntimeException;
use Smalot\PdfParser\Parser;

class CurpPdfTextExtractor
{
    public function __construct(
        private readonly Parser $parser
    ) {
    }

    public function extract(string $path): string
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('No se encontró el PDF temporal o no puede leerse.');
        }

        try {
            $pdf = $this->parser->parseFile($path);
            $text = trim($pdf->getText());
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'No fue posible leer el PDF. Verifica que no esté protegido o dañado.',
                previous: $e
            );
        }

        $text = $this->normalize($text);

        if ($text === '') {
            throw new RuntimeException(
                'El PDF no contiene texto seleccionable. Descarga nuevamente la CURP desde RENAPO en formato PDF.'
            );
        }

        if (mb_strlen($text) < 30) {
            throw new RuntimeException(
                'El PDF contiene muy poco texto para identificar los datos de la CURP.'
            );
        }

        return $text;
    }

    private function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\u{00A0}"], ["\n", "\n", ' '], $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
