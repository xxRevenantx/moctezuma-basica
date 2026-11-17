<?php

use App\Services\InformeAcademicoIaExportService;

function datosEjemploInformeAcademicoIa(): array
{
    return [
        'titulo' => 'Informe de rendimiento académico - Grupo A',
        'resumen_ejecutivo' => 'La captura del grupo se encuentra completa: lectura & escritura.',
        'diagnostico_pedagogico' => 'Dar seguimiento a los aprendizajes de todas las materias.',
        'fortalezas' => ['Captura completa', 'Alto porcentaje de aprobación'],
        'areas_atencion' => ['Integración Curricular'],
        'recomendaciones' => ['Revisar los registros antes de la siguiente evaluación'],
        'prioridad' => 'media',
        'aviso' => 'Documento orientativo sujeto a revisión por personal autorizado.',
    ];
}

function contextoEjemploInformeAcademicoIa(): array
{
    return [
        'nivel' => 'Secundaria',
        'grado' => '3°',
        'grupo' => 'A',
        'generacion' => '2024 - 2027',
        'periodo' => 'Primer periodo',
    ];
}

it('genera un nombre de archivo sin rutas ni caracteres especiales', function () {
    $servicio = app(InformeAcademicoIaExportService::class);
    $nombre = $servicio->nombreArchivo(contextoEjemploInformeAcademicoIa(), '17/11/2026 11:25', 'docx');

    expect($nombre)
        ->toStartWith('informe-academico-ia-')
        ->toEndWith('.docx')
        ->not->toContain('/')
        ->not->toContain(' ');
});

it('exporta el informe como PDF válido sin generar un segundo diagnóstico', function () {
    $servicio = app(InformeAcademicoIaExportService::class);
    $archivo = $servicio->generarPdf(
        datosEjemploInformeAcademicoIa(),
        contextoEjemploInformeAcademicoIa(),
        'consejo_tecnico',
        '17/11/2026 11:25'
    );

    expect($archivo)->toStartWith('%PDF-');
    expect(strlen($archivo))->toBeGreaterThan(2000);
});

it('exporta Word editable con todos los apartados de la vista', function () {
    $servicio = app(InformeAcademicoIaExportService::class);
    $archivo = $servicio->generarWord(
        datosEjemploInformeAcademicoIa(),
        contextoEjemploInformeAcademicoIa(),
        'consejo_tecnico',
        '17/11/2026 11:25'
    );

    expect(substr($archivo, 0, 2))->toBe('PK');

    $ruta = tempnam(sys_get_temp_dir(), 'test_informe_ia_');
    file_put_contents($ruta, $archivo);

    try {
        $zip = new ZipArchive();
        expect($zip->open($ruta))->toBeTrue();
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        expect($xml)->toBeString();
        $xmlValido = new DOMDocument();
        expect($xmlValido->loadXML($xml))->toBeTrue();

        foreach ([
            'Informe de rendimiento académico',
            'Consejo técnico escolar',
            'Resumen ejecutivo',
            'Fortalezas',
            'Áreas de atención',
            'Recomendaciones',
            'Diagnóstico pedagógico',
            'Integración Curricular',
            'Documento orientativo sujeto a revisión',
            'lectura &amp; escritura',
        ] as $texto) {
            expect($xml)->toContain($texto);
        }
    } finally {
        @unlink($ruta);
    }
});
