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

/**
 * Constancias editables con la misma estructura institucional de la vista PDF.
 *
 * Los datos se toman del Blade del PDF ya renderizado. No se reconstruyen ni se
 * recalculan los datos de los alumnos o sus calificaciones en este exportador.
 * Los textos y las tablas permanecen editables en Word; los tres elementos
 * gráficos institucionales quedan incrustados en cada sección del documento.
 */
class ConstanciaWordExporter
{
    private const ANCHO_UTIL = 10120; // Carta: 8.5 pulgadas menos los márgenes.

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
        $paginas = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " pagina ")]');
        if (!$paginas || $paginas->length === 0) {
            abort(422, 'No se encontró el contenido de la constancia para generar Word.');
        }

        $temporal = storage_path('app/temp/documentos-word');
        File::ensureDirectoryExists($temporal, 0775, true);
        Settings::setTempDir($temporal);
        $archivo = $temporal . DIRECTORY_SEPARATOR . Str::uuid() . '.docx';

        $word = new PhpWord();
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize(9.5);
        $word->getDocInfo()->setCreator('Centro Universitario Moctezuma');
        $word->getDocInfo()->setTitle('Constancia institucional editable');

        foreach ($paginas as $pagina) {
            if (!$pagina instanceof DOMElement) {
                continue;
            }
            // Cada constancia tiene su propia sección/página; en un lote también
            // se conservan los datos y los encabezados propios de cada alumno.
            $seccion = $word->addSection([
                'paperSize' => 'Letter',
                'orientation' => 'portrait',
                'marginLeft' => 980, 'marginRight' => 980,
                'marginTop' => 2350, 'marginBottom' => 760,
                'headerHeight' => 260, 'footerHeight' => 170,
            ]);
            $this->agregarEncabezado($seccion, $xpath, $pagina);
            $this->agregarPie($seccion);
            $this->agregarCuerpo($seccion, $xpath, $pagina);
        }

        IOFactory::createWriter($word, 'Word2007')->save($archivo);

        $nombre = basename(str_replace('\\', '/', $nombre));
        $nombre = preg_replace('/[^\pL\pN._ -]/u', '_', $nombre) ?: 'constancia.docx';
        if (!str_ends_with(strtolower($nombre), '.docx')) {
            $nombre .= '.docx';
        }

        return response()->download($archivo, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }

    private function agregarEncabezado($seccion, DOMXPath $xpath, DOMElement $pagina): void
    {
        $header = $seccion->addHeader();

        // La versión aclarada del logotipo se ancla detrás del texto, como en
        // el PDF, sin ocupar una fila ni volver ineditable el contenido.
        $marca = public_path('imagenes/constancia-marca-agua-word.png');
        if (is_file($marca)) {
            $header->addWatermark($marca, [
                'width' => 320, 'height' => 402,
                'marginTop' => 185, 'marginLeft' => 141,
            ]);
        }

        $tabla = $header->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $tabla->addRow();
        $izquierda = $tabla->addCell(2030, ['valign' => 'center']);
        $centro = $tabla->addCell(6320, ['valign' => 'center']);
        $derecha = $tabla->addCell(1770, ['valign' => 'center']);

        $logoGobierno = $this->buscarImagen($xpath, $pagina, 'logo-gobierno');
        if ($logoGobierno) {
            $izquierda->addImage($logoGobierno, ['width' => 108, 'height' => 45, 'alignment' => 'left']);
        }

        $datosEscuela = $this->buscar($xpath, $pagina, 'datos-escuela');
        if ($datosEscuela) {
            foreach ($this->lineas($datosEscuela) as $linea) {
                $centro->addText(mb_strtoupper($linea, 'UTF-8'),
                    ['name' => 'Arial', 'size' => 6.25, 'bold' => true, 'color' => '111111'],
                    ['alignment' => 'center', 'lineHeight' => 1.03, 'spaceAfter' => 0]);
            }
        }

        $logoInstitucion = $this->buscarImagen($xpath, $pagina, 'logo-institucion');
        if ($logoInstitucion) {
            $derecha->addImage($logoInstitucion,
                ['width' => 59, 'height' => 74, 'alignment' => 'right']);
        }

        $header->addText(' ', ['size' => 1], [
            'borderBottomSize' => 5, 'borderBottomColor' => '999999',
            'spaceBefore' => 0, 'spaceAfter' => 0,
        ]);
    }

    private function agregarPie($seccion): void
    {
        $tira = public_path('imagenes/constancia-tira-word.png');
        if (!is_file($tira)) {
            $tira = public_path('imagenes/tira.jpg');
        }
        if (is_file($tira)) {
            $footer = $seccion->addFooter();
            $footer->addImage($tira,
                ['width' => 502, 'height' => 29, 'alignment' => 'center']);
        }
    }

    private function agregarCuerpo($seccion, DOMXPath $xpath, DOMElement $pagina): void
    {
        $datos = $this->buscar($xpath, $pagina, 'bloque-datos');
        if ($datos) {
            foreach ($this->lineas($datos) as $linea) {
                $run = $seccion->addTextRun([
                    'alignment' => 'right', 'lineHeight' => 1.12, 'spaceAfter' => 35,
                ]);
                $this->agregarDato($run, $linea);
            }
        }

        $dirigido = $this->buscar($xpath, $pagina, 'dirigido');
        if ($dirigido) {
            $espacio = $this->tieneClase($dirigido, 'relaciones-exteriores') ? 3800 : 2500;
            foreach ($this->lineas($dirigido) as $indice => $linea) {
                $seccion->addText(mb_strtoupper($linea),
                    ['name' => 'Arial', 'size' => 9, 'bold' => true],
                    ['alignment' => 'left', 'spaceBefore' => $indice === 0 ? $espacio : 0,
                        'lineHeight' => 1.08, 'spaceAfter' => 0]);
            }
        }

        $principal = $this->buscar($xpath, $pagina, 'contenido-principal');
        if (!$principal) {
            return;
        }
        $conducta = $this->tieneClase($principal, 'conducta');

        foreach ($principal->childNodes as $nodo) {
            if (!$nodo instanceof DOMElement) {
                continue;
            }
            if ($this->tieneClase($nodo, 'parrafo')) {
                $this->agregarParrafo($seccion, $nodo, 10.8, [
                    'alignment' => 'both', 'lineHeight' => 1.34,
                    'spaceBefore' => 65, 'spaceAfter' => 0,
                ]);
            } elseif ($this->tieneClase($nodo, 'titulo-hace-constar')) {
                $estiloTitulo = ['alignment' => 'center',
                    'spaceBefore' => $conducta ? 320 : 400,
                    'spaceAfter' => $conducta ? 300 : 400];
                if ($conducta) {
                    // En conducta sólo el nombre del alumno va subrayado.
                    $run = $seccion->addTextRun($estiloTitulo);
                    foreach ($nodo->childNodes as $hijo) {
                        $this->agregarFragmento($run, $hijo,
                            ['name' => 'Arial', 'size' => 14.5, 'bold' => true]);
                    }
                } else {
                    $seccion->addText($this->texto($nodo),
                        ['name' => 'Arial', 'size' => 17.5,
                            'bold' => true, 'underline' => 'single'], $estiloTitulo);
                }
            } elseif ($this->tieneClase($nodo, 'contenido')) {
                $this->agregarContenidoLibre($seccion, $nodo, $conducta);
            } elseif ($this->tieneClase($nodo, 'texto-calificaciones')) {
                $this->agregarParrafo($seccion, $nodo, 10.5, [
                    'alignment' => 'both', 'lineHeight' => 1.15,
                    'spaceBefore' => 125, 'spaceAfter' => 105,
                ]);
            } elseif ($this->tieneClase($nodo, 'tabla-calificaciones')) {
                $this->agregarTabla($seccion, $nodo, true);
            } elseif ($this->tieneClase($nodo, 'parrafo-final')) {
                $this->agregarParrafo($seccion, $nodo, 11, [
                    'alignment' => 'both', 'lineHeight' => 1.34,
                    'spaceBefore' => $conducta ? 195 : 210, 'spaceAfter' => 0,
                ]);
            } elseif ($this->tieneClase($nodo, 'firma')) {
                $this->agregarFirma($seccion, $xpath, $nodo, $conducta);
            }
        }
    }

    private function agregarDato($run, string $linea): void
    {
        $estilo = ['name' => 'Arial', 'size' => 8.4, 'bold' => true];
        if (preg_match('/^(Asunto|ASUNTO):\s*(.+)$/iu', $linea, $m)) {
            $run->addText($m[1] . ': ', $estilo);
            $run->addText($m[2], array_merge($estilo, ['underline' => 'single']));
        } else {
            $run->addText($linea, $estilo);
        }
    }

    private function agregarFirma($seccion, DOMXPath $xpath, DOMElement $firma, bool $conducta): void
    {
        $atentamente = $this->buscar($xpath, $firma, 'atentamente');
        if ($atentamente) {
            // El cargo en el PDF es un <span> dentro de "ATENTAMENTE".
            $cargo = $this->buscar($xpath, $atentamente, 'cargo-firma');
            $titulo = $this->textoDirecto($atentamente);
            if ($titulo !== '') {
                $seccion->addText($titulo,
                    ['name' => 'Arial', 'size' => 8.5, 'bold' => true],
                    ['alignment' => 'center', 'spaceBefore' => $conducta ? 290 : 240,
                        'spaceAfter' => 0]);
            }
            if ($cargo) {
                $seccion->addText(mb_strtoupper($this->texto($cargo)),
                    ['name' => 'Arial', 'size' => 7.5, 'bold' => true],
                    ['alignment' => 'center', 'spaceAfter' => 0]);
            }
        }

        // Espacio para firma manuscrita: centrado y sin imagen que impida editar.
        $linea = $seccion->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $linea->addRow();
        $linea->addCell(3310);
        $linea->addCell(3500, [
            'borderBottomSize' => 5, 'borderBottomColor' => '999999',
        ])->addText(' ', ['size' => 5], [
            'spaceBefore' => $conducta ? 450 : 300, 'spaceAfter' => 0,
        ]);
        $linea->addCell(3310);

        if ($conducta) {
            $cargoFinal = $this->buscar($xpath, $firma, 'cargo-final-conducta');
            if ($cargoFinal) {
                $seccion->addText(mb_strtoupper($this->texto($cargoFinal)),
                    ['name' => 'Arial', 'size' => 8.5, 'bold' => true],
                    ['alignment' => 'center', 'spaceBefore' => 45, 'spaceAfter' => 0]);
            }
        }
        $nombre = $this->buscar($xpath, $firma, 'nombre-firma');
        if ($nombre) {
            $seccion->addText(mb_strtoupper($this->texto($nombre)),
                ['name' => 'Arial', 'size' => 8.5, 'bold' => true],
                ['alignment' => 'center', 'spaceBefore' => 25, 'spaceAfter' => 0]);
        }
    }

    private function agregarContenidoLibre($seccion, DOMElement $contenedor, bool $conducta): void
    {
        foreach ($contenedor->childNodes as $nodo) {
            if (!$nodo instanceof DOMElement) {
                continue;
            }
            if ($this->tieneClase($nodo, 'buena-conducta')) {
                $seccion->addText($this->texto($nodo),
                    ['name' => 'Arial', 'size' => 14, 'bold' => true],
                    ['alignment' => 'center', 'spaceBefore' => 260, 'spaceAfter' => 180]);
            } elseif ($nodo->tagName === 'table') {
                $this->agregarTabla($seccion, $nodo, false);
            } elseif (in_array(strtolower($nodo->tagName), ['ul', 'ol'], true)) {
                foreach ($nodo->childNodes as $item) {
                    if ($item instanceof DOMElement && strtolower($item->tagName) === 'li') {
                        $this->agregarParrafo($seccion, $item, 10.5, [
                            'alignment' => 'left', 'indent' => 260,
                            'lineHeight' => 1.25, 'spaceAfter' => 50,
                        ], true);
                    }
                }
            } elseif (in_array(strtolower($nodo->tagName), ['p', 'div', 'section', 'span', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote'], true)) {
                // Se respetan los saltos que contenga el editor de plantillas.
                $contieneBloques = false;
                foreach ($nodo->childNodes as $hijo) {
                    if ($hijo instanceof DOMElement && in_array(strtolower($hijo->tagName),
                        ['p', 'div', 'table', 'section', 'ul', 'ol', 'h1', 'h2', 'h3', 'blockquote'], true)) {
                        $contieneBloques = true;
                        break;
                    }
                }
                if ($contieneBloques) {
                    $this->agregarContenidoLibre($seccion, $nodo, $conducta);
                    continue;
                }
                $this->agregarParrafo($seccion, $nodo, 10.6, [
                    'alignment' => $this->alineacion($nodo, 'both'),
                    'lineHeight' => 1.34, 'spaceAfter' => 110,
                ]);
            }
        }
    }

    private function agregarParrafo($contenedor, DOMElement $nodo, float $tamano,
        array $parrafo, bool $lista = false): void
    {
        if ($this->texto($nodo) === '') {
            return;
        }
        $run = $contenedor->addTextRun($parrafo);
        $fuente = ['name' => 'Arial', 'size' => $tamano, 'color' => '000000'];
        if ($lista) {
            $run->addText('•  ', $fuente);
        }
        foreach ($nodo->childNodes as $hijo) {
            $this->agregarFragmento($run, $hijo, $fuente);
        }
    }

    private function agregarFragmento($run, DOMNode $nodo, array $fuente): void
    {
        if ($nodo instanceof DOMText) {
            $texto = preg_replace('/\s+/u', ' ', (string) $nodo->nodeValue) ?? '';
            if ($texto !== '') {
                $run->addText($texto, $fuente);
            }
            return;
        }
        if (!$nodo instanceof DOMElement) {
            return;
        }
        $etiqueta = strtolower($nodo->tagName);
        if ($etiqueta === 'br') {
            $run->addTextBreak();
            return;
        }
        if ($etiqueta === 'img' || in_array($etiqueta, ['script', 'iframe', 'svg', 'object'], true)) {
            return; // Nunca se descargan imágenes remotas de contenido editable.
        }
        if (in_array($etiqueta, ['b', 'strong'], true)) {
            $fuente['bold'] = true;
        }
        if (in_array($etiqueta, ['i', 'em'], true)) {
            $fuente['italic'] = true;
        }
        if (in_array($etiqueta, ['u', 'ins'], true)) {
            $fuente['underline'] = 'single';
        }
        if (in_array($etiqueta, ['s', 'strike', 'del'], true)) {
            $fuente['strikethrough'] = true;
        }
        $estilo = strtolower($nodo->getAttribute('style'));
        if (str_contains($estilo, 'font-weight: bold') || preg_match('/font-weight:\s*[6789]00/', $estilo)) {
            $fuente['bold'] = true;
        }
        if (str_contains($estilo, 'text-decoration: underline')) {
            $fuente['underline'] = 'single';
        }
        if (str_contains($estilo, 'font-style: italic')) {
            $fuente['italic'] = true;
        }
        foreach ($nodo->childNodes as $hijo) {
            $this->agregarFragmento($run, $hijo, $fuente);
        }
    }

    private function agregarTabla($seccion, DOMElement $nodo, bool $calificaciones): void
    {
        $filas = [];
        foreach ($nodo->getElementsByTagName('tr') as $tr) {
            $celdas = [];
            foreach ($tr->childNodes as $celda) {
                if ($celda instanceof DOMElement && in_array(strtolower($celda->tagName), ['th', 'td'], true)) {
                    $celdas[] = [
                        'texto' => $this->texto($celda),
                        'titulo' => strtolower($celda->tagName) === 'th',
                        'periodo' => $this->tieneClase($celda, 'celda-periodo'),
                    ];
                }
            }
            if ($celdas !== []) {
                $filas[] = $celdas;
            }
        }
        if ($filas === []) {
            return;
        }

        $columnas = max(array_map('count', $filas));
        $ancho = (int) floor(self::ANCHO_UTIL / max(1, $columnas));
        $tamano = $calificaciones ? max(5.0, min(7.0, 44 / $columnas)) : 8.0;
        $tabla = $seccion->addTable([
            'borderSize' => 5, 'borderColor' => '666666',
            'cellMargin' => $calificaciones ? 30 : 55,
        ]);
        foreach ($filas as $fila) {
            $tabla->addRow();
            foreach ($fila as $celdaDatos) {
                $fondo = $celdaDatos['periodo'] && $calificaciones ? '6B7280'
                    : ($celdaDatos['titulo'] ? 'D9D9D9' : 'FFFFFF');
                $celda = $tabla->addCell($ancho, [
                    'bgColor' => $fondo, 'valign' => 'center',
                ]);
                $celda->addText($celdaDatos['texto'], [
                    'name' => 'Arial', 'size' => $tamano,
                    'bold' => $celdaDatos['titulo'] || $celdaDatos['periodo'],
                    'color' => $fondo === '6B7280' ? 'FFFFFF' : '111111',
                ], ['alignment' => 'center', 'lineHeight' => 1.06, 'spaceAfter' => 0]);
            }
        }
    }

    private function buscar(DOMXPath $xpath, DOMElement $contexto, string $clase): ?DOMElement
    {
        $lista = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ' . $clase . ' ")]', $contexto);
        $primero = $lista?->item(0);
        return $primero instanceof DOMElement ? $primero : null;
    }

    private function buscarImagen(DOMXPath $xpath, DOMElement $pagina, string $clase): ?string
    {
        $imagen = $this->buscar($xpath, $pagina, $clase);
        if (!$imagen || strtolower($imagen->tagName) !== 'img') {
            return null;
        }
        $ruta = realpath($imagen->getAttribute('src'));
        $publico = realpath(public_path());
        if ($ruta && $publico && str_starts_with($ruta, $publico . DIRECTORY_SEPARATOR)
            && is_file($ruta)) {
            return $ruta;
        }
        return null;
    }

    private function lineas(DOMElement $elemento): array
    {
        $html = $elemento->ownerDocument->saveHTML($elemento);
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $texto = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lineas = array_map(fn ($linea) => trim(preg_replace('/\s+/u', ' ', $linea) ?? ''),
            preg_split('/\R/u', $texto) ?: []);
        return array_values(array_filter($lineas, fn ($linea) => $linea !== ''));
    }

    private function texto(DOMNode $nodo): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $nodo->textContent) ?? '');
    }

    private function textoDirecto(DOMElement $nodo): string
    {
        $texto = '';
        foreach ($nodo->childNodes as $hijo) {
            if ($hijo instanceof DOMText) {
                $texto .= $hijo->textContent;
            }
        }
        return trim(preg_replace('/\s+/u', ' ', $texto) ?? '');
    }

    private function tieneClase(DOMElement $nodo, string $clase): bool
    {
        return in_array($clase, preg_split('/\s+/', trim($nodo->getAttribute('class'))) ?: [], true);
    }

    private function alineacion(DOMElement $nodo, string $predeterminado): string
    {
        if (preg_match('/text-align:\s*(left|right|center|justify)/i', $nodo->getAttribute('style'), $m)) {
            return strtolower($m[1]) === 'justify' ? 'both' : strtolower($m[1]);
        }
        return $predeterminado;
    }
}
