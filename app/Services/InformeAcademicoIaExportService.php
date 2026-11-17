<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;
use RuntimeException;

/**
 * Exporta exactamente el informe que ya fue redactado con IA.
 * No consulta GroqCloud, no recupera calificaciones individuales y no persiste datos.
 */
class InformeAcademicoIaExportService
{
    private const TIPOS = [
        'pedagogico' => 'Pedagógico para docentes',
        'direccion' => 'Resumen para dirección',
        'consejo_tecnico' => 'Consejo técnico escolar',
        'familias' => 'Resumen para familias',
    ];

    /** @param array<string, mixed> $contexto */
    public function nombreArchivo(array $contexto, string $fecha, string $extension): string
    {
        $partes = [
            'informe-academico-ia',
            (string) ($contexto['nivel'] ?? ''),
            (string) ($contexto['grado'] ?? ''),
            (string) ($contexto['grupo'] ?? ''),
            str_replace(['/', ' ', ':'], ['', '-', ''], $fecha),
        ];

        $base = Str::slug(implode('-', array_filter($partes)), '-');

        return substr($base, 0, 160) . '.' . $extension;
    }

    /**
     * @param array<string, mixed> $informe
     * @param array<string, mixed> $contexto
     */
    public function generarPdf(array $informe, array $contexto, string $tipo, string $fecha): string
    {
        return Pdf::loadView('pdf.informe-academico-ia', $this->datos($informe, $contexto, $tipo, $fecha))
            ->setPaper('letter', 'portrait')
            ->output();
    }

    /**
     * @param array<string, mixed> $informe
     * @param array<string, mixed> $contexto
     */
    public function generarWord(array $informe, array $contexto, string $tipo, string $fecha): string
    {
        $datos = $this->datos($informe, $contexto, $tipo, $fecha);

        $directorio = storage_path('app/temp/informes-academicos-ia');
        $directorioPhpWord = $directorio . DIRECTORY_SEPARATOR . 'phpword';
        File::ensureDirectoryExists($directorio, 0775, true);
        File::ensureDirectoryExists($directorioPhpWord, 0775, true);

        if (!is_writable($directorio) || !is_writable($directorioPhpWord)) {
            throw new RuntimeException('No se puede escribir en el directorio temporal de informes de Word.');
        }

        // PHPWord necesita un temporal escribible también en entornos Windows/Laragon.
        Settings::setTempDir($directorioPhpWord);
        // Los informes pueden contener &, < o >: escapar XML para no corromper DOCX.
        Settings::setOutputEscapingEnabled(true);

        $documento = new PhpWord();
        $documento->setDefaultFontName('Arial');
        $documento->setDefaultFontSize(10);
        $documento->getDocInfo()->setCreator('Centro Universitario Moctezuma');
        $documento->getDocInfo()->setCompany('Centro Universitario Moctezuma');
        $documento->getDocInfo()->setTitle($datos['titulo']);
        $documento->getDocInfo()->setSubject('Informe académico grupal con apoyo de IA');

        $seccion = $documento->addSection([
            'paperSize' => 'Letter',
            'marginTop' => 1060,
            'marginBottom' => 1040,
            'marginLeft' => 1100,
            'marginRight' => 1100,
            'headerHeight' => 550,
            'footerHeight' => 550,
        ]);

        $azul = '006492';
        $texto = '263347';
        $muted = '64748B';

        $encabezado = $seccion->addTable(['borderBottomSize' => 12, 'borderBottomColor' => $azul, 'cellMarginBottom' => 90]);
        $encabezado->addRow();
        $logo = $datos['logo'];
        $celdaLogo = $encabezado->addCell(3050, ['valign' => 'center']);
        if ($logo !== null) {
            $celdaLogo->addImage($logo, ['width' => 142, 'alignment' => Jc::LEFT]);
        } else {
            $celdaLogo->addText('CENTRO UNIVERSITARIO MOCTEZUMA', ['bold' => true, 'size' => 11, 'color' => $azul]);
        }

        $celdaTitulo = $encabezado->addCell(5700, ['valign' => 'center']);
        $celdaTitulo->addText('INFORME ACADÉMICO', ['bold' => true, 'size' => 15, 'color' => $azul], ['alignment' => Jc::RIGHT]);
        $celdaTitulo->addText('Redactado con apoyo de inteligencia artificial', ['size' => 8, 'color' => $muted], ['alignment' => Jc::RIGHT]);

        $seccion->addTextBreak(1);
        $seccion->addText($datos['titulo'], ['bold' => true, 'size' => 15, 'color' => $texto], ['spaceAfter' => 150]);
        $seccion->addText('Tipo de informe: ' . $datos['tipoInforme'] . '   |   Prioridad: ' . ucfirst($datos['prioridad']),
            ['size' => 9, 'bold' => true, 'color' => $azul], ['spaceAfter' => 70]);
        $seccion->addText('Generado: ' . $fecha, ['size' => 8, 'color' => $muted], ['spaceAfter' => 170]);

        $metadatos = $this->etiquetasContexto($contexto);
        if ($metadatos !== []) {
            $tabla = $seccion->addTable([
                'borderSize' => 5,
                'borderColor' => 'DDE6EE',
                'cellMargin' => 75,
            ]);
            foreach (array_chunk($metadatos, 2) as $fila) {
                $tabla->addRow();
                foreach ($fila as $item) {
                    $celda = $tabla->addCell(4375, ['bgColor' => 'F4F8FC']);
                    $celda->addText($item[0], ['size' => 8, 'bold' => true, 'color' => $muted]);
                    $celda->addText($item[1], ['size' => 9, 'color' => $texto]);
                }
                if (count($fila) === 1) {
                    $tabla->addCell(4375, ['bgColor' => 'F4F8FC']);
                }
            }
            $seccion->addTextBreak(1);
        }

        $this->seccionTextoWord($seccion, 'Resumen ejecutivo', (string) ($informe['resumen_ejecutivo'] ?? ''), $azul);
        $this->seccionListaWord($seccion, 'Fortalezas', $informe['fortalezas'] ?? [], '047857');
        $this->seccionListaWord($seccion, 'Áreas de atención', $informe['areas_atencion'] ?? [], 'A16207');
        $this->seccionListaWord($seccion, 'Recomendaciones', $informe['recomendaciones'] ?? [], $azul);
        $this->seccionTextoWord($seccion, 'Diagnóstico pedagógico', (string) ($informe['diagnostico_pedagogico'] ?? ''), $azul);

        $seccion->addText('AVISO', ['bold' => true, 'size' => 8, 'color' => $muted], ['spaceBefore' => 160, 'spaceAfter' => 55]);
        $seccion->addText((string) ($informe['aviso'] ?? ''), ['size' => 9, 'italic' => true, 'color' => $muted], ['spaceAfter' => 120]);

        $pie = $seccion->addFooter();
        $pie->addText('Centro Universitario Moctezuma  |  Informe orientativo sujeto a revisión institucional',
            ['size' => 8, 'color' => $muted], ['alignment' => Jc::CENTER]);

        $ruta = tempnam($directorio, 'informe_ia_');
        if ($ruta === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal para el informe de Word.');
        }

        try {
            IOFactory::createWriter($documento, 'Word2007')->save($ruta);
            $contenido = file_get_contents($ruta);
            if ($contenido === false || $contenido === '') {
                throw new RuntimeException('No se pudo leer el documento de Word generado.');
            }

            return $contenido;
        } finally {
            if (is_file($ruta)) {
                @unlink($ruta);
            }
        }
    }

    /** @param array<string, mixed> $informe
     *  @param array<string, mixed> $contexto
     *  @return array<string, mixed>
     */
    private function datos(array $informe, array $contexto, string $tipo, string $fecha): array
    {
        $logo = public_path('logo.png');

        return [
            'informe' => $informe,
            'contexto' => $contexto,
            'titulo' => (string) ($informe['titulo'] ?? 'Informe académico grupal'),
            'prioridad' => in_array($informe['prioridad'] ?? '', ['baja', 'media', 'alta'], true)
                ? $informe['prioridad'] : 'media',
            'tipoInforme' => self::TIPOS[$tipo] ?? self::TIPOS['pedagogico'],
            'generadoEn' => $fecha,
            'logo' => is_file($logo) ? $logo : null,
            'contextoEtiquetado' => $this->etiquetasContexto($contexto),
        ];
    }

    /** @param array<string, mixed> $contexto
     *  @return array<int, array{string, string}>
     */
    private function etiquetasContexto(array $contexto): array
    {
        $etiquetas = [
            'nivel' => 'Nivel educativo',
            'generacion' => 'Generación',
            'grado' => 'Grado',
            'grupo' => 'Grupo',
            'semestre' => 'Semestre',
            'periodo' => 'Periodo evaluado',
            'estado_periodo' => 'Estado del periodo',
        ];

        $resultado = [];
        foreach ($etiquetas as $clave => $etiqueta) {
            if (!isset($contexto[$clave]) || !is_scalar($contexto[$clave]) || trim((string) $contexto[$clave]) === '') {
                continue;
            }
            $resultado[] = [$etiqueta, (string) $contexto[$clave]];
        }

        return $resultado;
    }

    private function seccionTextoWord($seccion, string $titulo, string $contenido, string $color): void
    {
        $seccion->addText($titulo, ['bold' => true, 'size' => 11, 'color' => $color],
            ['spaceBefore' => 150, 'spaceAfter' => 90, 'keepNext' => true]);
        $seccion->addText($contenido, ['size' => 10, 'color' => '263347'],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 110, 'lineHeight' => 1.28]);
    }

    private function seccionListaWord($seccion, string $titulo, mixed $elementos, string $color): void
    {
        $seccion->addText($titulo, ['bold' => true, 'size' => 11, 'color' => $color],
            ['spaceBefore' => 140, 'spaceAfter' => 85, 'keepNext' => true]);

        if (!is_array($elementos) || count($elementos) === 0) {
            $seccion->addText('Sin elementos señalados.', ['size' => 9, 'italic' => true, 'color' => '64748B']);
            return;
        }

        foreach ($elementos as $elemento) {
            if (!is_scalar($elemento) || trim((string) $elemento) === '') {
                continue;
            }
            $seccion->addText('•  ' . (string) $elemento, ['size' => 9, 'color' => '263347'],
                ['spaceAfter' => 72, 'indentation' => ['left' => 180], 'lineHeight' => 1.2]);
        }
    }
}
