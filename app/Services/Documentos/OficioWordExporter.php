<?php

namespace App\Services\Documentos;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Word de Oficios: misma información que el Blade PDF, diseño editable y propio. */
class OficioWordExporter
{
    public function descargar(string $vista, array $datos, string $nombre): BinaryFileResponse
    {
        $html = view($vista, $datos)->render();
        $dom = new DOMDocument('1.0', 'UTF-8');
        $previo = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8"?>' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previo);
        }
        $xpath = new DOMXPath($dom);
        $preescolar = $vista === 'pdf.oficio_preescolar_pdf';
        $oficio = $datos['oficio'];
        $nivel = $datos['nivel'] ?? null;
        $director = $datos['director'] ?? null;
        $lema = trim((string) ($datos['lema'] ?? ''));

        $carpeta = storage_path('app/temp/documentos-word');
        File::ensureDirectoryExists($carpeta, 0775, true);
        Settings::setTempDir($carpeta);
        $archivo = $carpeta . DIRECTORY_SEPARATOR . Str::uuid() . '.docx';
        $fuente = $preescolar ? 'Arial' : 'Times New Roman';
        $word = new PhpWord();
        $word->setDefaultFontName($fuente);
        $word->setDefaultFontSize(9.5);
        $word->getDocInfo()->setCreator('Centro Universitario Moctezuma');
        $word->getDocInfo()->setTitle('Oficio institucional ' . (string) $oficio->folio);
        $seccion = $word->addSection([
            'paperSize' => 'Letter', 'orientation' => 'portrait',
            'marginLeft' => 900, 'marginRight' => 900,
            'marginTop' => $preescolar ? 1710 : 2250,
            'marginBottom' => 840, 'headerHeight' => 330, 'footerHeight' => 275,
        ]);
        $this->encabezado($seccion, $xpath, $preescolar);
        $this->pie($seccion);
        $normal = ['name' => $fuente, 'size' => 9.5, 'color' => '000000'];
        $negrita = array_merge($normal, ['bold' => true]);
        $asunto = trim((string) ($oficio->asunto ?? ''));
        if ($asunto === '') {
            $asunto = $oficio->tipo_oficio === 'Alta' ? 'Alta por traslado.' : 'Baja por traslado.';
        }

        // Ficha de asunto y folio, a la derecha como el original.
        $tabla = $seccion->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $tabla->addRow();
        $tabla->addCell($preescolar ? 4800 : 7080);
        $celda = $tabla->addCell($preescolar ? 5640 : 3360);
        $info = ['spaceAfter' => 30, 'lineHeight' => 1.13];
        if ($preescolar) {
            $this->parDato($celda, 'DEPENDENCIA: ', 'CENTRO UNIVERSITARIO MOCTEZUMA', $normal, $info);
            $this->parDato($celda, 'C.C.T: ', (string) ($nivel?->cct ?? ''), $normal, $info);
            $this->parDato($celda, 'ASUNTO: ', $asunto, $normal, $info);
            $this->parDato($celda, 'NÚM. DE OFICIO: ', (string) $oficio->folio, $normal, $info);
        } else {
            $this->parDato($celda, 'Sección: ', (string) ($oficio->seccion ?: 'ADMINISTRATIVA'), $normal, $info);
            $this->parDato($celda, 'No. Oficio: ', (string) $oficio->folio, $normal, $info);
            $this->parDato($celda, 'Asunto: ', $asunto, $normal, $info);
        }
        if ($lema !== '') {
            $seccion->addText($preescolar ? '“' . $lema . '”' : $lema,
                ['name' => $fuente, 'size' => 9, 'bold' => true, 'italic' => true],
                ['alignment' => 'right', 'spaceBefore' => 100, 'spaceAfter' => 0]);
        }
        $seccion->addText(trim((string) ($oficio->fecha_lugar ?? '')),
            ['name' => $fuente, 'size' => 9.5, 'bold' => !$preescolar],
            ['alignment' => $preescolar ? 'center' : 'right', 'spaceBefore' => 170, 'spaceAfter' => $preescolar ? 120 : 300]);

        // Destinatarios: principal a la izquierda y "Con atención" a la derecha.
        $tablaDest = $seccion->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $tablaDest->addRow();
        $izq = $tablaDest->addCell(5270, ['valign' => 'top']);
        $der = $tablaDest->addCell(5170, ['valign' => 'top']);
        $dest = ['name' => $fuente, 'size' => 9, 'bold' => true];
        $izq->addText('', $dest, ['spaceAfter' => $preescolar ? 240 : 975]);
        foreach ([$oficio->dirigido_1_nombre, $oficio->dirigido_1_cargo, $oficio->dirigido_1_lugar] as $linea) {
            if (trim((string) $linea) !== '') {
                $izq->addText(mb_strtoupper(trim((string) $linea)), $dest,
                    ['spaceAfter' => 0, 'lineHeight' => 1.05]);
            }
        }
        if ($preescolar) {
            $izq->addText('PRESENTE.', $dest, ['spaceAfter' => 0]);
        }
        $der->addText('', [], ['spaceAfter' => $preescolar ? 600 : 1505]);
        $der->addText('CON ATENCIÓN', $dest, ['alignment' => 'center', 'spaceAfter' => 90]);
        foreach ([$oficio->dirigido_2_nombre, $oficio->dirigido_2_cargo, $oficio->dirigido_2_lugar] as $linea) {
            if (trim((string) $linea) !== '') {
                $der->addText(mb_strtoupper(trim((string) $linea)), $dest,
                    ['alignment' => 'center', 'spaceAfter' => 0, 'lineHeight' => 1.05]);
            }
        }
        $seccion->addText('', [], ['spaceAfter' => $preescolar ? 240 : 805]);

        // Texto principal procedente del Blade PDF: contiene nombres, CURP y texto del traslado.
        $contenido = $this->buscar($xpath, 'contenido');
        if ($contenido) {
            $this->contenido($seccion, $contenido, $normal, !$preescolar);
        }
        foreach (['tabla-alumno', 'tabla-calificaciones'] as $clase) {
            if ($clase === 'tabla-calificaciones') {
                $titulo = $this->buscar($xpath, 'texto-calificaciones');
                if ($titulo) {
                    $seccion->addText(trim($titulo->textContent),
                        ['name' => $fuente, 'size' => 8, 'bold' => true],
                        ['spaceBefore' => 100, 'spaceAfter' => 80]);
                }
            }
            $nodoTabla = $this->buscar($xpath, $clase);
            if ($nodoTabla) {
                $this->tablaEditable($seccion, $nodoTabla, $fuente, $clase === 'tabla-alumno' ? 8 : 7);
            }
        }
        $despedida = $this->buscar($xpath, 'despedida');
        if ($despedida) {
            $seccion->addText(trim($despedida->textContent), $normal,
                ['spaceBefore' => 150, 'spaceAfter' => 0]);
        }

        // Firma manuscrita y nombre, centrados; la línea sigue siendo editable.
        $nombreDirector = trim(implode(' ', array_filter([
            (string) ($director?->titulo ?? ''), (string) ($director?->nombre ?? ''),
            (string) ($director?->apellido_paterno ?? ''), (string) ($director?->apellido_materno ?? ''),
        ], fn ($s) => trim($s) !== '')));
        $seccion->addText('ATENTAMENTE', $negrita,
            ['alignment' => 'center', 'spaceBefore' => $preescolar ? 400 : 1450,
                'spaceAfter' => $preescolar ? 85 : 610]);
        if ($preescolar) {
            $seccion->addText('DIRECTORA', $negrita,
                ['alignment' => 'center', 'spaceAfter' => 360]);
        }
        $lineaFirma = $seccion->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $lineaFirma->addRow();
        $lineaFirma->addCell(3260);
        $lineaFirma->addCell(3920, ['borderBottomSize' => 6, 'borderBottomColor' => '999999'])
            ->addText(' ', [], ['spaceAfter' => 0]);
        $lineaFirma->addCell(3260);
        if (!$preescolar) {
            $seccion->addText(mb_strtoupper((string) ($director?->cargo ?? 'DIRECTOR(A) DE LA ESCUELA')),
                $negrita, ['alignment' => 'center', 'spaceBefore' => 100, 'spaceAfter' => 0]);
        }
        $seccion->addText(mb_strtoupper($nombreDirector), $negrita,
            ['alignment' => 'center', 'spaceBefore' => 10, 'spaceAfter' => 0]);

        IOFactory::createWriter($word, 'Word2007')->save($archivo);
        $nombre = basename(str_replace('\\', '/', $nombre));
        $nombre = preg_replace('/[^\pL\pN._ -]/u', '_', $nombre) ?: 'oficio.docx';
        if (!str_ends_with(strtolower($nombre), '.docx')) {
            $nombre .= '.docx';
        }
        return response()->download($archivo, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }

    private function encabezado($seccion, DOMXPath $xpath, bool $preescolar): void
    {
        $header = $seccion->addHeader();
        // Marca de agua translúcida, detrás del texto y no como imagen de contenido.
        $marca = public_path('imagenes/oficio-marca-agua-word.png');
        if (!$preescolar && is_file($marca)) {
            $header->addWatermark($marca, [
                'width' => 352, 'height' => 443, 'marginTop' => 160, 'marginLeft' => 135,
            ]);
        }
        $tabla = $header->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $tabla->addRow();
        $izq = $tabla->addCell($preescolar ? 3000 : 1800, ['valign' => 'center']);
        $centro = $tabla->addCell($preescolar ? 7440 : 6840, ['valign' => 'center']);
        $der = $preescolar ? null : $tabla->addCell(1800, ['valign' => 'center']);
        $logo = public_path('imagenes/' . ($preescolar ? 'logo-guerrero.png' : 'logo-edu.png'));
        if (!is_file($logo)) {
            $logo = public_path('imagenes/logo-edu.png');
        }
        if (is_file($logo)) {
            $izq->addImage($logo, ['width' => $preescolar ? 130 : 112, 'alignment' => 'left']);
        }
        $fuente = ['name' => $preescolar ? 'Arial' : 'Times New Roman',
            'size' => $preescolar ? 8.5 : 8.3, 'bold' => true];
        if ($preescolar) {
            $centro->addText('SUBSECRETARÍA DE EDUCACIÓN BÁSICA', $fuente,
                ['alignment' => 'right', 'spaceAfter' => 0]);
            $centro->addText('DIRECCIÓN GENERAL DE EDUCACIÓN INICIAL Y PREESCOLAR', $fuente,
                ['alignment' => 'right', 'spaceAfter' => 0]);
        } else {
            $datosEscuela = $this->buscar($xpath, 'datos-escuela');
            foreach ($datosEscuela ? $this->lineas($datosEscuela) : [] as $texto) {
                $centro->addText($texto, $fuente,
                    ['alignment' => 'center', 'spaceAfter' => 0, 'lineHeight' => 1.0]);
            }
            $logoMoctezuma = public_path('imagenes/logo-letra.png');
            if ($der && is_file($logoMoctezuma)) {
                $der->addImage($logoMoctezuma,
                    ['width' => 76, 'height' => 96, 'alignment' => 'right']);
            }
        }
        if (!$preescolar) {
            $header->addText('', [], [
                'borderBottomSize' => 5, 'borderBottomColor' => '888888',
                'spaceBefore' => 5, 'spaceAfter' => 0,
            ]);
        }
    }

    private function pie($seccion): void
    {
        $tira = public_path('imagenes/oficio-tira-word.png');
        if (!is_file($tira)) {
            $tira = public_path('imagenes/tira.jpg');
        }
        if (is_file($tira)) {
            $footer = $seccion->addFooter();
            $footer->addImage($tira, ['width' => 660, 'height' => 37, 'alignment' => 'center']);
        }
    }

    private function parDato($celda, string $etiqueta, string $valor, array $fuente, array $parrafo): void
    {
        $linea = $celda->addTextRun($parrafo);
        $linea->addText($etiqueta, array_merge($fuente, ['bold' => true]));
        $linea->addText($valor, $fuente);
    }

    private function buscar(DOMXPath $xpath, string $clase): ?DOMElement
    {
        $resultados = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ' . $clase . ' ")]');
        $nodo = $resultados?->item(0);
        return $nodo instanceof DOMElement ? $nodo : null;
    }

    private function lineas(DOMElement $elemento): array
    {
        $html = $elemento->ownerDocument->saveHTML($elemento);
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $texto = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lineas = array_map(fn ($s) => trim(preg_replace('/\s+/u', ' ', $s) ?? ''),
            preg_split('/\R/u', $texto) ?: []);
        return array_values(array_filter($lineas, fn ($s) => $s !== ''));
    }

    private function contenido($seccion, DOMElement $padre, array $fuente, bool $mayusculas): void
    {
        foreach ($padre->childNodes as $nodo) {
            if (trim((string) $nodo->textContent) === '') {
                continue;
            }
            if ($nodo instanceof DOMElement && in_array(strtolower($nodo->tagName), ['div', 'section'], true)) {
                $contieneBloques = false;
                foreach ($nodo->childNodes as $hijo) {
                    if ($hijo instanceof DOMElement && in_array(strtolower($hijo->tagName), ['p', 'div', 'table', 'ul', 'ol'], true)) {
                        $contieneBloques = true;
                        break;
                    }
                }
                if ($contieneBloques) {
                    $this->contenido($seccion, $nodo, $fuente, $mayusculas);
                    continue;
                }
            }
            if ($nodo instanceof DOMElement && strtolower($nodo->tagName) === 'table') {
                $this->tablaEditable($seccion, $nodo, $fuente['name'], 8);
                continue;
            }
            if ($nodo instanceof DOMElement && in_array(strtolower($nodo->tagName), ['ul', 'ol'], true)) {
                foreach ($nodo->childNodes as $li) {
                    if ($li instanceof DOMElement && strtolower($li->tagName) === 'li') {
                        $this->parrafo($seccion, $li, $fuente, $mayusculas, true);
                    }
                }
                continue;
            }
            $this->parrafo($seccion, $nodo, $fuente, $mayusculas);
        }
    }

    private function parrafo($seccion, DOMNode $nodo, array $fuente, bool $mayusculas, bool $lista = false): void
    {
        if (trim((string) $nodo->textContent) === '') {
            return;
        }
        $estilo = ['alignment' => 'both', 'lineHeight' => 1.14, 'spaceAfter' => 110];
        if ($lista) {
            $estilo['indent'] = 320;
        }
        $run = $seccion->addTextRun($estilo);
        if ($lista) {
            $run->addText('•  ', $fuente);
        }
        if ($nodo instanceof DOMText) {
            $texto = preg_replace('/\s+/u', ' ', $nodo->textContent) ?? '';
            $run->addText($mayusculas ? mb_strtoupper(trim($texto)) : trim($texto), $fuente);
        } else {
            foreach ($nodo->childNodes as $hijo) {
                $this->fragmento($run, $hijo, $fuente, $mayusculas);
            }
        }
    }

    private function fragmento($run, DOMNode $nodo, array $fuente, bool $mayusculas): void
    {
        if ($nodo instanceof DOMText) {
            $texto = preg_replace('/\s+/u', ' ', $nodo->textContent) ?? '';
            if (trim($texto) !== '') {
                $run->addText($mayusculas ? mb_strtoupper($texto) : $texto, $fuente);
            }
            return;
        }
        if (!$nodo instanceof DOMElement) {
            return;
        }
        $tag = strtolower($nodo->tagName);
        if ($tag === 'br') {
            $run->addTextBreak();
            return;
        }
        if (in_array($tag, ['b', 'strong'], true)) {
            $fuente['bold'] = true;
        }
        if ($tag === 'u') {
            $fuente['underline'] = 'single';
        }
        if (in_array($tag, ['i', 'em'], true)) {
            $fuente['italic'] = true;
        }
        foreach ($nodo->childNodes as $hijo) {
            $this->fragmento($run, $hijo, $fuente, $mayusculas);
        }
    }

    private function tablaEditable($seccion, DOMElement $nodo, string $fuente, float $tamano): void
    {
        $filas = [];
        foreach ($nodo->getElementsByTagName('tr') as $tr) {
            $celdas = [];
            foreach ($tr->childNodes as $celda) {
                if ($celda instanceof DOMElement && in_array(strtolower($celda->tagName), ['th', 'td'], true)) {
                    $celdas[] = [trim(preg_replace('/\s+/u', ' ', $celda->textContent) ?? ''), strtolower($celda->tagName) === 'th'];
                }
            }
            if ($celdas) {
                $filas[] = $celdas;
            }
        }
        if (!$filas) {
            return;
        }
        $columnas = max(array_map('count', $filas));
        $ancho = (int) floor(10440 / max(1, $columnas));
        $tabla = $seccion->addTable(['borderSize' => 5, 'borderColor' => '777777', 'cellMargin' => 40]);
        foreach ($filas as $fila) {
            $tabla->addRow();
            foreach ($fila as [$texto, $titulo]) {
                $celda = $tabla->addCell($ancho, $titulo ? ['bgColor' => 'E8ECF1'] : []);
                $celda->addText($texto, ['name' => $fuente, 'size' => $tamano, 'bold' => $titulo],
                    ['alignment' => 'center', 'spaceAfter' => 0]);
            }
        }
    }
}
