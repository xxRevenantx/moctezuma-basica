<?php

namespace App\Services;

use DateTimeImmutable;

class TutorDocumentParser
{
    /** @var array<string, string> */
    private const ESTADOS_ABREVIADOS = [
        'AGS' => 'Aguascalientes', 'BC' => 'Baja California', 'BCS' => 'Baja California Sur',
        'CAMP' => 'Campeche', 'COAH' => 'Coahuila', 'COL' => 'Colima', 'CHIS' => 'Chiapas',
        'CHIH' => 'Chihuahua', 'CDMX' => 'Ciudad de México', 'DF' => 'Ciudad de México',
        'DGO' => 'Durango', 'GTO' => 'Guanajuato', 'GRO' => 'Guerrero', 'HGO' => 'Hidalgo',
        'JAL' => 'Jalisco', 'MEX' => 'Estado de México', 'MICH' => 'Michoacán', 'MOR' => 'Morelos',
        'NAY' => 'Nayarit', 'NL' => 'Nuevo León', 'OAX' => 'Oaxaca', 'PUE' => 'Puebla',
        'QRO' => 'Querétaro', 'QROO' => 'Quintana Roo', 'SLP' => 'San Luis Potosí',
        'SIN' => 'Sinaloa', 'SON' => 'Sonora', 'TAB' => 'Tabasco', 'TAMPS' => 'Tamaulipas',
        'TLAX' => 'Tlaxcala', 'VER' => 'Veracruz', 'YUC' => 'Yucatán', 'ZAC' => 'Zacatecas',
    ];

    public function __construct(
        private readonly CurpPdfParser $curpPdfParser,
        private readonly CurpDataDecoder $curpDecoder
    ) {
    }

    /**
     * @return array{campos: array<string, ?string>, advertencias: array<int, string>}
     */
    public function parse(string $rawText, string $documentType): array
    {
        $text = $this->normalize($rawText);
        $lines = $this->lines($text);
        $documentType = in_array($documentType, ['ine', 'curp'], true) ? $documentType : 'ine';

        $fields = $documentType === 'curp'
            ? $this->parseCurpDocument($text, $lines)
            : $this->parseIneDocument($text, $lines);

        $curp = $this->extractCurp($text);
        if ($curp !== null) {
            $fields['curp'] = $curp;
            $decoded = $this->curpDecoder->decode($curp);

            foreach (['fecha_nacimiento', 'genero', 'estado_nacimiento'] as $field) {
                if (blank($fields[$field] ?? null) && filled($decoded[$field] ?? null)) {
                    $fields[$field] = $decoded[$field];
                }
            }
        }

        $fields = $this->normalizeFields($fields);
        $warnings = [];

        if (blank($fields['curp'] ?? null)) {
            $warnings[] = 'No se identificó una CURP válida. Puedes aplicar los demás campos y capturarla manualmente.';
        }

        if (blank($fields['nombre'] ?? null) || blank($fields['apellido_paterno'] ?? null)) {
            $warnings[] = 'El nombre no pudo separarse completamente. Revisa el texto reconocido antes de guardar.';
        }

        if ($documentType === 'ine' && blank($fields['calle'] ?? null)) {
            $warnings[] = 'El domicilio no está visible o no pudo reconocerse en la credencial.';
        }

        return [
            'campos' => $fields,
            'advertencias' => $warnings,
        ];
    }

    /** @return array<string, ?string> */
    private function parseCurpDocument(string $text, array $lines): array
    {
        $parsed = $this->curpPdfParser->parse($text);

        $name = $this->valueAfterLabel($lines, ['NOMBRE(S)', 'NOMBRES', 'NOMBRE']);
        $paternal = $this->valueAfterLabel($lines, ['PRIMER APELLIDO', 'APELLIDO PATERNO']);
        $maternal = $this->valueAfterLabel($lines, ['SEGUNDO APELLIDO', 'APELLIDO MATERNO']);

        return [
            'curp' => $parsed['curp'] ?? null,
            'nombre' => $name ?: ($parsed['nombres'] ?? null),
            'apellido_paterno' => $paternal ?: ($parsed['apellido_paterno'] ?? null),
            'apellido_materno' => $maternal ?: ($parsed['apellido_materno'] ?? null),
            'fecha_nacimiento' => $this->extractDate($text),
            'genero' => $this->extractGender($text),
            'estado_nacimiento' => $this->valueAfterLabel($lines, ['ENTIDAD DE NACIMIENTO', 'ESTADO DE NACIMIENTO']),
            'ciudad_nacimiento' => null,
            'municipio_nacimiento' => null,
            'calle' => null,
            'numero' => null,
            'colonia' => null,
            'ciudad' => null,
            'municipio' => null,
            'estado' => null,
            'codigo_postal' => null,
        ];
    }

    /** @return array<string, ?string> */
    private function parseIneDocument(string $text, array $lines): array
    {
        [$name, $paternal, $maternal] = $this->extractIneName($lines);
        $address = $this->extractIneAddress($lines);

        return [
            'curp' => null,
            'nombre' => $name,
            'apellido_paterno' => $paternal,
            'apellido_materno' => $maternal,
            'fecha_nacimiento' => $this->extractDate($text),
            'genero' => $this->extractGender($text),
            'estado_nacimiento' => null,
            'ciudad_nacimiento' => null,
            'municipio_nacimiento' => null,
            ...$address,
        ];
    }

    /** @return array{0: ?string, 1: ?string, 2: ?string} */
    private function extractIneName(array $lines): array
    {
        $explicitName = $this->valueAfterLabel($lines, ['NOMBRE(S)', 'NOMBRES']);
        $explicitPaternal = $this->valueAfterLabel($lines, ['APELLIDO PATERNO', 'PRIMER APELLIDO']);
        $explicitMaternal = $this->valueAfterLabel($lines, ['APELLIDO MATERNO', 'SEGUNDO APELLIDO']);

        if ($explicitName || $explicitPaternal || $explicitMaternal) {
            return [$explicitName, $explicitPaternal, $explicitMaternal];
        }

        $start = $this->findLineIndex($lines, ['NOMBRE']);
        if ($start === null) {
            return [null, null, null];
        }

        $sameLine = trim((string) preg_replace('/^NOMBRE\s*[:\-]?\s*/u', '', $lines[$start]));
        if ($sameLine !== '' && $sameLine !== $lines[$start] && $this->looksLikePersonName($sameLine)) {
            return $this->splitIneOrderedName($sameLine);
        }

        $candidates = [];
        $stopWords = [
            'DOMICILIO', 'CLAVE DE ELECTOR', 'CURP', 'FECHA DE NACIMIENTO', 'SEXO',
            'AÑO DE REGISTRO', 'VIGENCIA', 'SECCION', 'ESTADO', 'MUNICIPIO', 'LOCALIDAD',
        ];

        for ($i = $start + 1; $i < min(count($lines), $start + 8); $i++) {
            $line = $lines[$i];
            if ($this->startsWithAny($line, $stopWords)) {
                break;
            }

            $clean = $this->cleanPersonName($line);
            if ($clean !== '' && $this->looksLikePersonName($clean)) {
                $candidates[] = $clean;
            }
        }

        if (count($candidates) >= 3) {
            return [implode(' ', array_slice($candidates, 2)), $candidates[0], $candidates[1]];
        }

        if (count($candidates) === 2) {
            return [$candidates[1], $candidates[0], null];
        }

        if (count($candidates) === 1) {
            return $this->splitFullName($candidates[0]);
        }

        return [null, null, null];
    }

    /** @return array<string, ?string> */
    private function extractIneAddress(array $lines): array
    {
        $defaults = [
            'calle' => null, 'numero' => null, 'colonia' => null, 'ciudad' => null,
            'municipio' => null, 'estado' => null, 'codigo_postal' => null,
        ];

        $start = $this->findLineIndex($lines, ['DOMICILIO']);
        if ($start === null) {
            return $defaults;
        }

        $stopWords = [
            'CLAVE DE ELECTOR', 'CURP', 'FECHA DE NACIMIENTO', 'SEXO', 'AÑO DE REGISTRO',
            'SECCION', 'VIGENCIA', 'EMISION', 'OCR', 'CIC', 'IDENTIFICADOR CIUDADANO',
        ];
        $addressLines = [];

        $sameLine = trim((string) preg_replace('/^DOMICILIO\s*[:\-]?\s*/u', '', $lines[$start]));
        if ($sameLine !== '') {
            $addressLines[] = $sameLine;
        }

        for ($i = $start + 1; $i < min(count($lines), $start + 8); $i++) {
            $line = $lines[$i];
            if ($this->startsWithAny($line, $stopWords)) {
                break;
            }
            if (mb_strlen($line) >= 3) {
                $addressLines[] = $line;
            }
        }

        if ($addressLines === []) {
            return $defaults;
        }

        $joined = implode(' ', $addressLines);
        preg_match('/\b(\d{5})\b/', $joined, $postalMatch);
        $postalCode = $postalMatch[1] ?? null;

        $streetLine = $addressLines[0] ?? '';
        $number = null;
        if (preg_match('/(?:\s|^)(S\/?N|SN|\d+[A-Z]?(?:\s*(?:INT|INTERIOR)\s*\w+)?)\s*$/u', $streetLine, $numberMatch)) {
            $number = $numberMatch[1];
            $streetLine = trim(mb_substr($streetLine, 0, -mb_strlen($numberMatch[0])));
        }
        $streetLine = preg_replace('/^(?:C|CALLE|AV|AVENIDA|BLVD|BOULEVARD)\.?\s+/u', '', $streetLine) ?? $streetLine;

        $colonyLine = $addressLines[1] ?? '';
        $colonyLine = preg_replace('/\b\d{5}\b/u', '', $colonyLine) ?? $colonyLine;
        $colonyLine = preg_replace('/^(?:COL|COLONIA|FRACC|FRACCIONAMIENTO|BARRIO)\.?\s+/u', '', trim($colonyLine)) ?? trim($colonyLine);

        $locationLine = trim(implode(' ', array_slice($addressLines, 2)));
        $locationLine = preg_replace('/\b\d{5}\b/u', '', $locationLine) ?? $locationLine;
        $locationParts = array_values(array_filter(array_map('trim', preg_split('/[,;]+/u', $locationLine) ?: [])));

        $state = null;
        if ($locationParts !== []) {
            $last = preg_replace('/[^A-ZÁÉÍÓÚÜÑ]/u', '', strtoupper((string) end($locationParts))) ?? '';
            $state = self::ESTADOS_ABREVIADOS[$last] ?? $this->expandStateFromText((string) end($locationParts));
            if ($state !== null) {
                array_pop($locationParts);
            }
        }

        $municipality = $locationParts !== [] ? implode(', ', $locationParts) : null;

        return [
            'calle' => $streetLine !== '' ? $streetLine : null,
            'numero' => $number,
            'colonia' => $colonyLine !== '' ? $colonyLine : null,
            'ciudad' => $municipality,
            'municipio' => $municipality,
            'estado' => $state,
            'codigo_postal' => $postalCode,
        ];
    }

    private function extractCurp(string $text): ?string
    {
        $upper = strtoupper($text);
        $preferred = [];
        $fallback = [];

        if (preg_match_all('/[A-Z0-9][A-Z0-9\s\-]{16,28}[A-Z0-9]/u', $upper, $matches)) {
            foreach ($matches[0] as $match) {
                $compact = preg_replace('/[^A-Z0-9]/u', '', $match) ?? '';
                for ($offset = 0; $offset <= max(0, strlen($compact) - 18); $offset++) {
                    $candidate = $this->repairCurpOcr(substr($compact, $offset, 18));

                    foreach ($this->curpOcrVariants($candidate) as $variant) {
                        if (! $this->curpDecoder->decode($variant)['valida']) {
                            continue;
                        }

                        if ($this->hasValidCurpCheckDigit($variant)) {
                            $preferred[] = $variant;
                        } else {
                            $fallback[] = $variant;
                        }
                    }
                }
            }
        }

        return $preferred[0] ?? $fallback[0] ?? null;
    }


    /** @return array<int, string> */
    private function curpOcrVariants(string $candidate): array
    {
        $variants = [$candidate];
        if (strlen($candidate) !== 18) {
            return $variants;
        }

        $ambiguous = [
            'O' => '0', '0' => 'O', 'I' => '1', '1' => 'I',
            'Z' => '2', '2' => 'Z', 'S' => '5', '5' => 'S', 'B' => '8', '8' => 'B',
        ];

        $homoclave = $candidate[16];
        if (isset($ambiguous[$homoclave])) {
            $alternative = $candidate;
            $alternative[16] = $ambiguous[$homoclave];
            $variants[] = $alternative;
        }

        return array_values(array_unique($variants));
    }

    private function hasValidCurpCheckDigit(string $curp): bool
    {
        if (strlen($curp) !== 18 || ! ctype_digit($curp[17])) {
            return false;
        }

        $characters = preg_split('//u', '0123456789ABCDEFGHIJKLMNÑOPQRSTUVWXYZ', -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $values = array_flip($characters);
        $sum = 0;

        for ($index = 0; $index < 17; $index++) {
            $character = $curp[$index];
            if (! isset($values[$character])) {
                return false;
            }
            $sum += $values[$character] * (18 - $index);
        }

        $expected = (10 - ($sum % 10)) % 10;
        return $expected === (int) $curp[17];
    }

    private function repairCurpOcr(string $candidate): string
    {
        $candidate = strtoupper($candidate);
        $chars = str_split($candidate);
        $numericPositions = [4, 5, 6, 7, 8, 9, 17];
        $letterPositions = [0, 1, 2, 3, 10, 11, 12, 13, 14, 15];

        foreach ($numericPositions as $position) {
            if (! isset($chars[$position])) {
                continue;
            }
            $chars[$position] = strtr($chars[$position], ['O' => '0', 'Q' => '0', 'I' => '1', 'L' => '1', 'Z' => '2', 'S' => '5', 'B' => '8']);
        }

        foreach ($letterPositions as $position) {
            if (! isset($chars[$position])) {
                continue;
            }
            $chars[$position] = strtr($chars[$position], ['0' => 'O', '1' => 'I', '2' => 'Z', '5' => 'S', '8' => 'B']);
        }

        return implode('', $chars);
    }

    private function extractDate(string $text): ?string
    {
        $patterns = [
            '/(?:FECHA\s+DE\s+NACIMIENTO|NACIMIENTO)\s*[:\-]?\s*(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, strtoupper($text), $match)) {
                $date = DateTimeImmutable::createFromFormat('!j/n/Y', "{$match[1]}/{$match[2]}/{$match[3]}");
                if ($date && $date->format('j/n/Y') === ((int) $match[1]) . '/' . ((int) $match[2]) . '/' . $match[3]) {
                    return $date->format('Y-m-d');
                }
            }
        }

        return null;
    }

    private function extractGender(string $text): ?string
    {
        $upper = strtoupper($text);

        if (preg_match('/\bSEXO\s*[:\-]?\s*(H|M|HOMBRE|MUJER|MASCULINO|FEMENINO)\b/u', $upper, $match)) {
            return match ($match[1]) {
                'H', 'HOMBRE', 'MASCULINO' => 'M',
                'M', 'MUJER', 'FEMENINO' => 'F',
                default => null,
            };
        }

        return null;
    }

    private function valueAfterLabel(array $lines, array $labels): ?string
    {
        foreach ($lines as $index => $line) {
            foreach ($labels as $label) {
                if (! str_starts_with($line, $label)) {
                    continue;
                }

                $sameLine = trim((string) preg_replace('/^' . preg_quote($label, '/') . '\s*[:\-]?\s*/u', '', $line));
                if ($sameLine !== '' && $sameLine !== $line) {
                    return $sameLine;
                }

                $next = $lines[$index + 1] ?? null;
                if ($next && ! $this->looksLikeLabel($next)) {
                    return $next;
                }
            }
        }

        return null;
    }

    private function findLineIndex(array $lines, array $labels): ?int
    {
        foreach ($lines as $index => $line) {
            if ($this->startsWithAny($line, $labels)) {
                return $index;
            }
        }

        return null;
    }

    private function startsWithAny(string $line, array $labels): bool
    {
        foreach ($labels as $label) {
            if (str_starts_with($line, $label)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeLabel(string $line): bool
    {
        return $this->startsWithAny($line, [
            'CURP', 'NOMBRE', 'APELLIDO', 'FECHA', 'SEXO', 'DOMICILIO', 'CLAVE', 'ENTIDAD',
            'ESTADO', 'MUNICIPIO', 'LOCALIDAD', 'AÑO', 'VIGENCIA', 'FOLIO',
        ]);
    }

    private function looksLikePersonName(string $value): bool
    {
        if (preg_match('/\d/u', $value)) {
            return false;
        }

        $words = preg_split('/\s+/u', $value) ?: [];
        return count($words) >= 1 && count($words) <= 6 && mb_strlen($value) <= 80;
    }

    private function cleanPersonName(string $value): string
    {
        $value = preg_replace('/[^A-ZÁÉÍÓÚÜÑ\s\'\-]/u', ' ', strtoupper($value)) ?? $value;
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    /** @return array{0: ?string, 1: ?string, 2: ?string} */
    private function splitFullName(string $fullName): array
    {
        $tokens = preg_split('/\s+/u', trim($fullName)) ?: [];
        if (count($tokens) < 3) {
            return [$fullName ?: null, null, null];
        }

        $maternal = array_pop($tokens);
        $paternal = array_pop($tokens);
        $names = implode(' ', $tokens);

        return [$names ?: null, $paternal ?: null, $maternal ?: null];
    }


    /** @return array{0: ?string, 1: ?string, 2: ?string} */
    private function splitIneOrderedName(string $fullName): array
    {
        $tokens = preg_split('/\s+/u', trim($fullName)) ?: [];
        if (count($tokens) < 3) {
            return [$fullName ?: null, null, null];
        }

        $paternal = array_shift($tokens);
        $maternal = array_shift($tokens);
        $names = implode(' ', $tokens);

        return [$names ?: null, $paternal ?: null, $maternal ?: null];
    }

    private function expandStateFromText(string $value): ?string
    {
        $normalized = trim((string) preg_replace('/[^A-ZÁÉÍÓÚÜÑ\s]/u', '', strtoupper($value)));
        if ($normalized === '') {
            return null;
        }

        foreach (self::ESTADOS_ABREVIADOS as $abbreviation => $state) {
            if ($normalized === strtoupper($state) || $normalized === $abbreviation) {
                return $state;
            }
        }

        return mb_strlen($normalized) >= 4 ? $normalized : null;
    }

    /** @return array<string, ?string> */
    private function normalizeFields(array $fields): array
    {
        $allFields = [
            'curp', 'nombre', 'apellido_paterno', 'apellido_materno', 'fecha_nacimiento', 'genero',
            'ciudad_nacimiento', 'municipio_nacimiento', 'estado_nacimiento', 'calle', 'numero',
            'colonia', 'ciudad', 'municipio', 'estado', 'codigo_postal',
        ];

        $normalized = [];
        foreach ($allFields as $field) {
            $value = $fields[$field] ?? null;
            if (is_string($value)) {
                // Tesseract suele confundir la letra I aislada de nombres de calles con una barra vertical.
                $value = preg_replace('/(?<=\s)\|(?=\s)/u', 'I', $value) ?? $value;
                $value = trim((string) preg_replace('/\s+/u', ' ', $value));
            }
            $normalized[$field] = blank($value) ? null : $value;
        }

        return $normalized;
    }

    private function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\u{00A0}"], ["\n", "\n", ' '], $text);
        $text = mb_strtoupper($text, 'UTF-8');
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /** @return array<int, string> */
    private function lines(string $text): array
    {
        return array_values(array_filter(array_map(
            static fn (string $line): string => trim((string) preg_replace('/\s+/u', ' ', $line)),
            explode("\n", $text)
        ), static fn (string $line): bool => $line !== ''));
    }
}
