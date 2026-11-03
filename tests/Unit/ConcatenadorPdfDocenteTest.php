<?php

use App\Services\Documentos\ConcatenadorPdfDocente;
use setasign\Fpdi\Fpdi;

it('combina las hojas de diferentes niveles sin cambiar tamaño ni orientación', function () {
    $asistencia = new FPDF('L', 'mm', 'legal');
    $asistencia->AddPage();
    $asistencia->SetFont('Arial', 'B', 14);
    $asistencia->Cell(50, 10, 'Asistencia: Secundaria');

    $otraAsistencia = new FPDF('L', 'mm', 'legal');
    $otraAsistencia->AddPage();
    $otraAsistencia->SetFont('Arial', 'B', 14);
    $otraAsistencia->Cell(50, 10, 'Asistencia: Bachillerato');

    $consolidador = new ConcatenadorPdfDocente();
    try {
        expect($consolidador->agregar($asistencia->Output('S')))->toBe(1)
            ->and($consolidador->agregar($otraAsistencia->Output('S')))->toBe(1)
            ->and($consolidador->documentos())->toBe(2)
            ->and($consolidador->paginas())->toBe(2);

        $ruta = tempnam(sys_get_temp_dir(), 'test_docente_pdf_');
        try {
            $consolidador->guardar($ruta);
            $verificador = new Fpdi();
            expect($verificador->setSourceFile($ruta))->toBe(2);
            for ($pagina = 1; $pagina <= 2; $pagina++) {
                $medidas = $verificador->getTemplateSize($verificador->importPage($pagina));
                expect($medidas['orientation'])->toBe('L')
                    ->and($medidas['width'])->toBeGreaterThan($medidas['height']);
            }
        } finally {
            @unlink($ruta);
        }
    } finally {
        $consolidador->limpiar();
    }
});

it('tambien preserva tamaño carta vertical de las evaluaciones', function () {
    $fuente = new FPDF('P', 'mm', 'letter');
    $fuente->AddPage();
    $fuente->SetFont('Arial', '', 12);
    $fuente->Cell(50, 10, 'Evaluacion: Primaria');
    $consolidador = new ConcatenadorPdfDocente();
    $ruta = tempnam(sys_get_temp_dir(), 'test_evaluaciones_');
    try {
        $consolidador->agregar($fuente->Output('S'));
        $consolidador->guardar($ruta);
        $lector = new Fpdi();
        expect($lector->setSourceFile($ruta))->toBe(1);
        $medidas = $lector->getTemplateSize($lector->importPage(1));
        expect($medidas['orientation'])->toBe('P')
            ->and($medidas['height'])->toBeGreaterThan($medidas['width']);
    } finally {
        $consolidador->limpiar();
        @unlink($ruta);
    }
});

it('rechaza contenido que no sea un PDF', function () {
    $consolidador = new ConcatenadorPdfDocente();
    try {
        $consolidador->agregar('not a pdf');
    } finally {
        $consolidador->limpiar();
    }
})->throws(InvalidArgumentException::class);
