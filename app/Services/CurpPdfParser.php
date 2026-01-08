<?php

namespace App\Services;

class CurpPdfParser
{
    public function parse(string $text): array
    {
        // Normaliza saltos de línea
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // -------------------------
        // 1) CURP (18 chars)
        // -------------------------
        $curp = null;
        if (preg_match('/\b[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d\b/', $text, $m)) {
            $curp = $m[0];
        }

        // -------------------------
        // 2) Nombre completo (patrón RENAPO)
        //    NOMBRE COMPLETO
        //    18 dígitos (folio)
        //    CURP
        // -------------------------
        $fullName = null;

        if ($curp) {
            $pattern = '/(?m)^\s*([A-ZÑÜ][A-ZÑÜ\s]{8,})\s*\n\s*(\d{10,20})\s*\n\s*('
                . preg_quote($curp, '/')
                . ')\s*$/';

            if (preg_match($pattern, $text, $m2)) {
                $fullName = $this->cleanNameLine($m2[1]);
            }
        }

        // -------------------------
        // 3) Fallback: línea mayúscula "cercana" a la CURP
        // -------------------------
        if (!$fullName && $curp) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", $text))));

            $idxCurp = null;
            foreach ($lines as $i => $line) {
                if (str_contains($line, $curp)) {
                    $idxCurp = $i;
                    break;
                }
            }

            if ($idxCurp !== null) {
                // Busca hacia arriba unas 10 líneas una candidata a nombre
                for ($k = $idxCurp - 1; $k >= max(0, $idxCurp - 12); $k--) {
                    $candidate = $lines[$k] ?? '';
                    if ($this->isLikelyFullName($candidate)) {
                        $fullName = $this->cleanNameLine($candidate);
                        break;
                    }
                }

                // Si no encontró arriba, busca hacia abajo
                if (!$fullName) {
                    for ($k = $idxCurp + 1; $k <= min(count($lines) - 1, $idxCurp + 12); $k++) {
                        $candidate = $lines[$k] ?? '';
                        if ($this->isLikelyFullName($candidate)) {
                            $fullName = $this->cleanNameLine($candidate);
                            break;
                        }
                    }
                }
            }
        }

        // -------------------------
        // 4) Si ya tengo fullName, lo separo en partes
        //    (últimas 2 palabras = apellidos, resto = nombres)
        // -------------------------
        $nombres = null;
        $apPat = null;
        $apMat = null;

        if ($fullName) {
            [$nombres, $apPat, $apMat] = $this->splitFullNameMxBasic($fullName);
        }

        return [
            'curp' => $curp,
            'nombre_completo' => $fullName,

            // ✅ para que tu applyExtractedCurpToForm() los agarre directo
            'nombres' => $nombres,
            'apellido_paterno' => $apPat,
            'apellido_materno' => $apMat,
        ];
    }

    private function isLikelyFullName(string $line): bool
    {
        $line = trim($line);
        if ($line === '')
            return false;

        // Solo mayúsculas y espacios (incluye Ñ/Ü)
        if (!preg_match('/^[A-ZÑÜ\s]{10,}$/', $line))
            return false;

        // Debe tener al menos 3 palabras (nombre + 2 apellidos)
        $parts = preg_split('/\s+/', $line) ?: [];
        if (count($parts) < 3)
            return false;

        // Evitar líneas institucionales típicas
        $ban = ['ESTADOS', 'MEXICANOS', 'SECRETARIA', 'GOBERNACION', 'REGISTRO', 'RENAPO', 'CONSTITUCION'];
        $u = strtoupper($line);
        foreach ($ban as $w) {
            if (str_contains($u, $w))
                return false;
        }

        return true;
    }

    private function cleanNameLine(string $line): string
    {
        $line = preg_replace('/\s+/', ' ', trim($line)) ?? trim($line);
        return $line;
    }

    private function splitFullNameMxBasic(string $full): array
    {
        $full = preg_replace('/\s+/', ' ', trim($full)) ?? trim($full);
        $tokens = preg_split('/\s+/', $full) ?: [];

        if (count($tokens) < 3) {
            return [$full, '', null];
        }

        $apMat = array_pop($tokens);
        $apPat = array_pop($tokens);
        $nombres = implode(' ', $tokens);

        return [$nombres, $apPat, $apMat];
    }
}
