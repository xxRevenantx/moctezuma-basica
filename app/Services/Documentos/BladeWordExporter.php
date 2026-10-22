<?php

namespace App\Services\Documentos;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Html;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Crea una versión editable del MISMO Blade que alimenta al PDF.
 * No convierte una imagen/PDF en texto ni altera el PDF existente.
 * Los elementos flotantes de Dompdf (marcas de agua decorativas) se omiten;
 * texto, tablas, encabezados y logos locales se conservan en el DOCX.
 */
class BladeWordExporter
{
    public function descargar(string $vista, array $datos, string $nombre, string $orientacion = 'portrait'): BinaryFileResponse
    {
        $html = view($vista, $datos)->render();
        return $this->descargarHtml($html, $nombre, $orientacion);
    }

    public function descargarHtml(string $html, string $nombre, string $orientacion = 'portrait'): BinaryFileResponse
    {
        $temporal = storage_path('app/temp/documentos-word');
        File::ensureDirectoryExists($temporal, 0775, true);
        Settings::setTempDir($temporal);

        $archivo = $temporal . DIRECTORY_SEPARATOR . Str::uuid() . '.docx';
        $paginas = $this->paginasEditables($html);
        $this->crearDocumento($paginas, $archivo, $orientacion);

        $nombre = basename(str_replace('\\', '/', $nombre));
        $nombre = preg_replace('/[^\pL\pN._ -]/u', '_', $nombre) ?: 'documento.docx';
        if (! str_ends_with(strtolower($nombre), '.docx')) {
            $nombre .= '.docx';
        }

        return response()->download($archivo, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }

    private function crearDocumento(array $paginas, string $ruta, string $orientacion): void
    {
        $word = new PhpWord();
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize($orientacion === 'landscape' ? 9 : 10);
        $word->getDocInfo()->setCreator('Centro Universitario Moctezuma');
        $word->getDocInfo()->setTitle('Documento institucional editable');

        $seccion = $word->addSection([
            'paperSize' => 'Letter',
            'orientation' => $orientacion === 'landscape' ? 'landscape' : 'portrait',
            'marginTop' => 1000,
            'marginBottom' => 1000,
            'marginLeft' => 1050,
            'marginRight' => 1050,
        ]);

        try {
            foreach ($paginas as $indice => $pagina) {
                if ($indice > 0) {
                    $seccion->addPageBreak();
                }
                Html::addHtml($seccion, $pagina, false, false);
            }
        } catch (Throwable $error) {
            // Una etiqueta HTML de una plantilla administrativa nunca debe
            // impedir descargar la información. Se conserva el texto editable.
            report($error);
            $word = new PhpWord();
            $word->setDefaultFontName('Arial');
            $word->setDefaultFontSize(10);
            $seccion = $word->addSection([
                'paperSize' => 'Letter',
                'orientation' => $orientacion === 'landscape' ? 'landscape' : 'portrait',
                'marginTop' => 1000, 'marginBottom' => 1000,
                'marginLeft' => 1050, 'marginRight' => 1050,
            ]);
            foreach ($paginas as $indice => $pagina) {
                if ($indice > 0) {
                    $seccion->addPageBreak();
                }
                $texto = trim(strip_tags(str_ireplace(
                    ['</p>', '</div>', '</tr>', '</li>', '<br>', '<br/>', '<br />'],
                    ["\n", "\n", "\n", "\n", "\n", "\n", "\n"],
                    $pagina
                )));
                foreach (preg_split('/\R/u', html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?: [] as $linea) {
                    if (trim($linea) !== '') {
                        $seccion->addText(trim($linea));
                    }
                }
            }
        }
        IOFactory::createWriter($word, 'Word2007')->save($ruta);
    }

    /** @return list<string> */
    private function paginasEditables(string $html): array
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $anterior = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8"?>' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($anterior);
        }

        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//script|//style|//noscript|//iframe|//svg|//object|//form|//button') as $nodo) {
            $nodo->parentNode?->removeChild($nodo);
        }
        foreach ($xpath->query('//*') as $nodo) {
            if (! $nodo instanceof DOMElement) {
                continue;
            }
            $clases = ' ' . $nodo->getAttribute('class') . ' ';
            // Las marcas y franjas decorativas no deben tapar contenido en Word.
            if (preg_match('/\b(marca-agua|watermark|footer-strip|tira|pie)\b/i', $clases)) {
                $nodo->parentNode?->removeChild($nodo);
                continue;
            }
            foreach (iterator_to_array($nodo->attributes ?? []) as $atributo) {
                if (str_starts_with(strtolower($atributo->name), 'on')) {
                    $nodo->removeAttribute($atributo->name);
                }
            }
            if (strtolower($nodo->tagName) === 'img') {
                $ruta = $this->imagenLocal($nodo->getAttribute('src'));
                if ($ruta === null) {
                    $nodo->parentNode?->removeChild($nodo);
                    continue;
                }
                $nodo->setAttribute('src', $ruta);
                $nodo->setAttribute('style', 'width:110px;height:auto;');
                if (preg_match('/\b(logo-gobierno|logo-guerrero|seg)\b/i', $clases)) {
                    $nodo->setAttribute('style', 'width:130px;height:auto;');
                }
            } else {
                $estilo = $this->estiloSeguro($nodo, $clases);
                $nodo->setAttribute('style', $estilo);
            }
        }

        $paginas = [];
        $nodosPagina = $xpath->query('//body//*[contains(concat(" ", normalize-space(@class), " "), " pagina ") or contains(concat(" ", normalize-space(@class), " "), " page ")]');
        foreach ($nodosPagina as $nodo) {
            if ($nodo instanceof DOMElement && ! $this->esPaginaAnidada($nodo)) {
                $paginas[] = $dom->saveHTML($nodo);
            }
        }
        if ($paginas === []) {
            $body = $dom->getElementsByTagName('body')->item(0);
            $paginas[] = $body ? $dom->saveHTML($body) : '<p>Documento sin contenido.</p>';
        }
        return array_values(array_filter($paginas, fn ($pagina) => trim(strip_tags($pagina)) !== '')) ?: ['<p>Documento vacío.</p>'];
    }

    private function esPaginaAnidada(DOMElement $elemento): bool
    {
        for ($padre = $elemento->parentNode; $padre instanceof DOMElement; $padre = $padre->parentNode) {
            if (preg_match('/\b(pagina|page)\b/', $padre->getAttribute('class'))) {
                return true;
            }
        }
        return false;
    }

    private function estiloSeguro(DOMElement $nodo, string $clases): string
    {
        $estilos = [];
        $actual = strtolower($nodo->getAttribute('style'));
        foreach (['text-align', 'font-weight', 'font-style', 'text-decoration', 'color'] as $propiedad) {
            if (preg_match('/(?:^|;)\s*' . preg_quote($propiedad, '/') . '\s*:\s*([^;]+)/', $actual, $coincide)) {
                $valor = trim($coincide[1]);
                if (preg_match('/^[a-z0-9#(),.%\s-]{1,70}$/', $valor)) {
                    $estilos[] = $propiedad . ':' . $valor;
                }
            }
        }
        if (preg_match('/\b(fecha|bloque-datos|subject|date|lugar-fecha)\b/', $clases)) {
            $estilos[] = 'text-align:right';
        }
        if (preg_match('/\b(titulo-hace-constar|atentamente|firma|titulo|encabezado-titulo)\b/', $clases)) {
            $estilos[] = 'text-align:center';
            $estilos[] = 'font-weight:bold';
        }
        if (preg_match('/\b(dirigido|recipient|asunto)\b/', $clases)) {
            $estilos[] = 'font-weight:bold';
        }
        if (preg_match('/\b(contenido|parrafo|body|parrafo-final)\b/', $clases)) {
            $estilos[] = 'text-align:justify';
        }
        if (strtolower($nodo->tagName) === 'table') {
            $estilos[] = 'width:100%';
            if (preg_match('/\b(tabla-calificaciones|reporte|tabla-datos|grid)\b/', $clases)) {
                $estilos[] = 'border:1px solid #777777';
            }
        }
        return implode(';', $estilos) . ';';
    }

    private function imagenLocal(string $ruta): ?string
    {
        if (preg_match('#^data:image/(?:png|jpeg|gif|bmp);base64,[a-z0-9+/=]+$#i', $ruta)) {
            return $ruta;
        }
        if ($ruta === '' || preg_match('#^(https?:)?//#i', $ruta)) {
            return null;
        }
        $real = realpath($ruta);
        if ($real === false || ! is_file($real)) {
            return null;
        }
        $permitidos = array_filter([realpath(public_path()), realpath(storage_path())]);
        foreach ($permitidos as $base) {
            if (str_starts_with($real, $base . DIRECTORY_SEPARATOR) && filesize($real) <= 5 * 1024 * 1024) {
                return $real;
            }
        }
        return null;
    }
}
