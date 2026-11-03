<?php

namespace App\Services\Documentos;

use InvalidArgumentException;
use RuntimeException;
use setasign\Fpdi\Fpdi;

/**
 * Consolida los PDF originales conservando páginas, tamaño y orientación.
 *
 * No se regeneran las plantillas en otro formato: FPDI importa cada página
 * tal y como fue emitida por DomPDF (oficio horizontal o carta vertical).
 */
class ConcatenadorPdfDocente
{
    private Fpdi $pdf;

    /** @var list<string> */
    private array $temporales = [];

    private int $documentos = 0;

    private int $paginas = 0;

    public function __construct()
    {
        $this->pdf = new Fpdi();
        $this->pdf->SetTitle('Listas docentes consolidadas', true);
        $this->pdf->SetAuthor('Centro Universitario Moctezuma', true);
        $this->pdf->SetCreator('Moctezuma Basica', true);
        $this->pdf->SetAutoPageBreak(false);
    }

    public function agregar(string $contenido): int
    {
        if (! str_starts_with(ltrim($contenido), '%PDF-')) {
            throw new InvalidArgumentException('El documento recibido no es un PDF valido.');
        }

        $ruta = tempnam(sys_get_temp_dir(), 'doc_profesor_pdf_');
        if ($ruta === false) {
            throw new RuntimeException('No se pudo crear un archivo temporal para unir los PDF.');
        }

        $escritos = file_put_contents($ruta, $contenido);
        if ($escritos === false || $escritos !== strlen($contenido)) {
            @unlink($ruta);
            throw new RuntimeException('No se pudo almacenar el PDF temporal.');
        }

        // Se conserva la fuente hasta escribir el PDF final: FPDI puede leer
        // recursos de sus páginas al construir el documento consolidado.
        $this->temporales[] = $ruta;
        $paginas = $this->pdf->setSourceFile($ruta);
        if ($paginas < 1) {
            throw new RuntimeException('El PDF original no contiene paginas.');
        }

        for ($numero = 1; $numero <= $paginas; $numero++) {
            $plantilla = $this->pdf->importPage($numero);
            $tamano = $this->pdf->getTemplateSize($plantilla);
            $this->pdf->AddPage($tamano['orientation'], [$tamano['width'], $tamano['height']]);
            $this->pdf->useTemplate($plantilla, 0, 0, $tamano['width'], $tamano['height']);
        }

        $this->documentos++;
        $this->paginas += $paginas;

        return $paginas;
    }

    public function documentos(): int
    {
        return $this->documentos;
    }

    public function paginas(): int
    {
        return $this->paginas;
    }

    public function guardar(string $destino): void
    {
        if ($this->paginas === 0) {
            throw new RuntimeException('No hay paginas para generar el PDF consolidado.');
        }

        $this->pdf->Output('F', $destino);
    }

    public function limpiar(): void
    {
        foreach ($this->temporales as $archivo) {
            @unlink($archivo);
        }
        $this->temporales = [];
    }

    public function __destruct()
    {
        $this->limpiar();
    }
}
