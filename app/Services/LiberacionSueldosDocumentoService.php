<?php

namespace App\Services;

use App\Models\LiberacionSueldo;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;

class LiberacionSueldosDocumentoService
{
    /** @var array<int, string> */
    private array $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public function fechaLarga(CarbonInterface|string|null $fecha): string
    {
        if (! $fecha) {
            return '';
        }

        $carbon = $fecha instanceof CarbonInterface ? $fecha : Carbon::parse($fecha);

        return $carbon->day . ' de ' . $this->meses[$carbon->month] . ' del ' . $carbon->year;
    }

    /** @return array<string, mixed> */
    public function aArray(LiberacionSueldo|array $documento): array
    {
        $datos = $documento instanceof LiberacionSueldo ? $documento->toArray() : $documento;
        $datos['fecha_documento_texto'] = $this->fechaLarga($datos['fecha_documento'] ?? null);
        $datos['fecha_reanudacion_texto'] = $this->fechaLarga($datos['fecha_reanudacion'] ?? null);
        $datos['director_articulo'] = $this->articuloCargo((string) ($datos['director_cargo'] ?? ''));
        $datos['supervisor_articulo'] = $this->articuloCargo((string) ($datos['supervisor_cargo'] ?? ''));
        $datos['jefe_sector_articulo'] = $this->articuloCargo((string) ($datos['jefe_sector_cargo'] ?? ''));
        $datos['logo_data_uri'] = $this->imagenDataUri(
            $datos['logo_encabezado_path'] ?? null,
            'imagenes/liberacion-sueldos/logo-encabezado.png'
        );
        $datos['franja_data_uri'] = $this->imagenDataUri(
            $datos['franja_inferior_path'] ?? null,
            'images/franja-inferior.png'
        );
        $datos['franja_ancho_mm'] = (float) ($datos['franja_ancho_mm'] ?? 200);
        $datos['franja_alto_mm'] = (float) ($datos['franja_alto_mm'] ?? 5.5);
        $datos['franja_inferior_mm'] = (float) ($datos['franja_inferior_mm'] ?? 4);

        $esDirectivo = (bool) ($datos['destinatario_es_directivo'] ?? false)
            || ($datos['tipo_firmantes'] ?? '') === 'supervision_sector';

        if ($esDirectivo) {
            $datos['firma_izquierda_nombre'] = (string) ($datos['supervisor_nombre'] ?? '');
            $datos['firma_izquierda_cargo'] = (string) ($datos['supervisor_cargo'] ?? 'SUPERVISOR ESCOLAR');
            $datos['firma_izquierda_articulo'] = $datos['supervisor_articulo'];
            $datos['firma_izquierda_es_direccion'] = false;
            $datos['firma_derecha_nombre'] = (string) ($datos['jefe_sector_nombre'] ?? '');
            $datos['firma_derecha_cargo'] = (string) ($datos['jefe_sector_cargo'] ?? 'JEFE DE SECTOR');
            $datos['firma_derecha_articulo'] = $datos['jefe_sector_articulo'];
        } else {
            $datos['firma_izquierda_nombre'] = (string) ($datos['director_nombre'] ?? '');
            $datos['firma_izquierda_cargo'] = (string) ($datos['director_cargo'] ?? 'DIRECTOR');
            $datos['firma_izquierda_articulo'] = $datos['director_articulo'];
            $datos['firma_izquierda_es_direccion'] = true;
            $datos['firma_derecha_nombre'] = (string) ($datos['supervisor_nombre'] ?? '');
            $datos['firma_derecha_cargo'] = (string) ($datos['supervisor_cargo'] ?? 'SUPERVISOR ESCOLAR');
            $datos['firma_derecha_articulo'] = $datos['supervisor_articulo'];
        }

        $datos['firma_izquierda_cargo_texto'] = trim(
            $datos['firma_izquierda_articulo'] . ' ' .
            $datos['firma_izquierda_cargo'] .
            ($datos['firma_izquierda_es_direccion'] ? ' DE LA ESCUELA' : '')
        );
        $datos['firma_derecha_cargo_texto'] = trim(
            $datos['firma_derecha_articulo'] . ' ' . $datos['firma_derecha_cargo']
        );

        return $datos;
    }

    public function rutaLogoLocal(?string $configurada = null): string
    {
        return $this->rutaImagenLocal($configurada, 'imagenes/liberacion-sueldos/logo-encabezado.png');
    }

    public function rutaFranjaLocal(?string $configurada = null): string
    {
        return $this->rutaImagenLocal($configurada, 'images/franja-inferior.png');
    }

    /**
     * @param Collection<int, LiberacionSueldo|array<string, mixed>> $documentos
     */
    public function crearWord(Collection $documentos, string $rutaSalida): void
    {
        $tempDir = storage_path('app/temp/phpword');
        File::ensureDirectoryExists($tempDir, 0775, true);
        Settings::setTempDir($tempDir);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);
        $phpWord->getDocInfo()
            ->setCreator('Centro Universitario Moctezuma')
            ->setCompany('Centro Universitario Moctezuma')
            ->setTitle('Constancia de Liberación de sueldos');

        foreach ($documentos->values() as $documento) {
            $d = $this->aArray($documento);
            $franjaAltoPt = max(6, (float) $d['franja_alto_mm'] * 2.83465);
            $franjaInferiorPt = max(0, (float) $d['franja_inferior_mm'] * 2.83465);

            $section = $phpWord->addSection([
                'pageSizeW' => 12240,
                'pageSizeH' => 15840,
                'marginTop' => 430,
                'marginRight' => 520,
                'marginBottom' => 420,
                'marginLeft' => 520,
                'headerHeight' => 150,
                'footerHeight' => (int) round(($franjaAltoPt + $franjaInferiorPt + 4) * 20),
            ]);

            $footer = $section->addFooter();
            if ($franjaInferiorPt > 0) {
                $footer->addText('', [], ['spaceAfter' => (int) round($franjaInferiorPt * 20)]);
            }
            $footer->addImage($this->rutaFranjaLocal($d['franja_inferior_path'] ?? null), [
                'width' => max(142, (float) $d['franja_ancho_mm'] * 2.83465),
                'height' => $franjaAltoPt,
                'alignment' => Jc::CENTER,
            ]);

            $encabezado = $section->addTable([
                'width' => 100 * 50,
                'unit' => 'pct',
                'borderSize' => 0,
                'cellMargin' => 0,
            ]);
            $encabezado->addRow(820);
            $celdaLogo = $encabezado->addCell(5200, ['valign' => 'top']);
            $celdaLogo->addImage($this->rutaLogoLocal($d['logo_encabezado_path'] ?? null), [
                'width' => 255,
                'height' => 56,
                'alignment' => Jc::LEFT,
            ]);
            $celdaTitulo = $encabezado->addCell(6200, ['valign' => 'center']);
            foreach ([
                'SECRETARÍA DE EDUCACIÓN',
                $d['encabezado_subsecretaria'] ?? 'SUBSECRETARÍA DE EDUCACIÓN BÁSICA',
                $d['encabezado_direccion'],
                'DEPARTAMENTO DE ADMINISTRACIÓN Y DESARROLLO DE PERSONAL',
            ] as $linea) {
                $celdaTitulo->addText($linea, ['bold' => true, 'size' => 9.5], [
                    'alignment' => Jc::CENTER,
                    'spaceAfter' => 0,
                    'lineHeight' => 1.0,
                ]);
            }

            $section->addTextBreak(1);
            $this->agregarDatoSubrayado($section, 'NIVEL:', (string) $d['nivel_nombre']);
            $this->agregarDatoSubrayado($section, 'ASUNTO:', 'Constancia de Liberación de sueldos.');
            $section->addTextBreak(1);

            $fecha = $section->addTextRun(['alignment' => Jc::RIGHT, 'spaceAfter' => 230]);
            $fecha->addText('__________________________, Gro., a ', ['bold' => true]);
            $fecha->addText((string) $d['fecha_documento_texto'] . '.', ['bold' => true, 'underline' => 'single']);

            $destinatario = $section->addTextRun(['spaceAfter' => 0]);
            $destinatario->addText('C. PROFR. (A): ', ['bold' => true, 'size' => 11]);
            $destinatario->addText((string) $d['trabajador_nombre'], ['bold' => true, 'size' => 11, 'underline' => 'single']);
            $section->addText('P R E S E N T E.', ['bold' => true, 'size' => 11], ['spaceAfter' => 330]);

            $p1 = $section->addTextRun(['alignment' => Jc::BOTH, 'spaceAfter' => 180, 'lineHeight' => 1.0]);
            $p1->addText('El que suscribe C. ', ['bold' => true]);
            $p1->addText((string) ($d['director_nombre'] ?: '____________________________'), ['bold' => true, 'underline' => 'single']);
            $p1->addText(' en mi carácter de ', ['bold' => true]);
            $p1->addText((string) $d['director_cargo'], ['bold' => true, 'underline' => 'single']);
            $p1->addText(' del C.T. ', ['bold' => true]);
            $p1->addText((string) $d['escuela_nombre'], ['bold' => true, 'underline' => 'single']);
            $p1->addText(', C.C.T. ', ['bold' => true]);
            $p1->addText((string) $d['cct'], ['bold' => true, 'underline' => 'single']);
            $p1->addText(' ubicada en ', ['bold' => true]);
            $p1->addText(trim((string) $d['localidad'] . ', ' . (string) $d['municipio']), ['bold' => true, 'underline' => 'single']);
            $p1->addText(', Gro., después de haber cumplido con toda la documentación y actividades relacionadas con el fin de cursos, tengo a bien autorizar el cobro correspondiente a la(s) quincena(s) ', ['bold' => true]);
            $p1->addText($d['quincena_inicio'] . ' y ' . $d['quincena_fin'] . ' del año ' . $d['anio'], ['bold' => true, 'underline' => 'single']);
            $p1->addText(' en la(s) clave(s) presupuestal(es):', ['bold' => true]);

            $section->addText((string) ($d['clave_presupuestal'] ?: 'S/N'), ['bold' => true, 'size' => 11], [
                'alignment' => Jc::CENTER,
                'spaceBefore' => 80,
                'spaceAfter' => 230,
                'borderBottomSize' => 8,
                'borderBottomColor' => '000000',
            ]);

            $section->addText(
                'Lo anterior por haber cumplido con la normatividad establecida del Ciclo Escolar ' . ($d['ciclo_escolar'] ?: '') . ' en función al nombramiento que se le ha conferido.',
                ['bold' => true],
                ['alignment' => Jc::BOTH, 'spaceAfter' => 180, 'lineHeight' => 1.0]
            );

            $p3 = $section->addTextRun(['alignment' => Jc::BOTH, 'spaceAfter' => 260, 'lineHeight' => 1.0]);
            $p3->addText('Asimismo, aprovecho la ocasión para hacer de su conocimiento que la reanudación de labores será el día ', ['bold' => true]);
            $p3->addText((string) ($d['fecha_reanudacion_texto'] ?: '________________'), ['bold' => true, 'underline' => 'single']);
            $p3->addText(', de acuerdo a lo establecido en el calendario escolar emitido por la Secretaría de Educación Pública (SEP) y a las disposiciones generales de inicio de cursos ' . $this->cicloSiguiente($d['ciclo_escolar'] ?? null) . '.', ['bold' => true]);

            $firmas = $section->addTable([
                'width' => 100 * 50,
                'unit' => 'pct',
                'borderSize' => 0,
                'cellMargin' => 20,
            ]);
            $firmas->addRow();

            $f1 = $firmas->addCell(5600, ['valign' => 'top']);
            $f1->addText('A T E N T A M E N T E', ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            $f1->addText(mb_strtoupper((string) $d['firma_izquierda_cargo_texto']), ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 380]);
            $f1->addText('_______________________________', ['bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            $f1->addText(mb_strtoupper((string) $d['firma_izquierda_nombre']), ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);

            $f2 = $firmas->addCell(5600, ['valign' => 'top']);
            $f2->addText('Vo.    Bo.', ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            $f2->addText(mb_strtoupper((string) $d['firma_derecha_cargo_texto']), ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 380]);
            $f2->addText('_______________________________', ['bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            $f2->addText(mb_strtoupper((string) $d['firma_derecha_nombre']), ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($rutaSalida);
    }

    private function agregarDatoSubrayado($section, string $etiqueta, string $valor): void
    {
        $tabla = $section->addTable([
            'width' => 72 * 50,
            'unit' => 'pct',
            'alignment' => JcTable::CENTER,
            'borderSize' => 0,
            'cellMargin' => 0,
        ]);
        $tabla->addRow(260);
        $tabla->addCell(1500)->addText($etiqueta, ['bold' => true, 'size' => 11], ['alignment' => Jc::LEFT, 'spaceAfter' => 0]);
        $celda = $tabla->addCell(6500, [
            'borderBottomSize' => 10,
            'borderBottomColor' => '000000',
        ]);
        $celda->addText($valor, ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
    }

    private function articuloCargo(string $cargo): string
    {
        $cargo = mb_strtoupper($cargo);

        return str_contains($cargo, 'DIRECTORA')
            || str_contains($cargo, 'SUPERVISORA')
            || str_contains($cargo, 'JEFA')
            ? 'LA'
            : 'EL';
    }

    private function cicloSiguiente(?string $ciclo): string
    {
        if (! $ciclo || ! preg_match('/^(\d{4})-(\d{4})$/', $ciclo, $m)) {
            return '';
        }

        return ((int) $m[1] + 1) . '-' . ((int) $m[2] + 1);
    }

    private function rutaImagenLocal(?string $configurada, string $predeterminada): string
    {
        if ($configurada) {
            try {
                if (Storage::disk('public')->exists($configurada)) {
                    return Storage::disk('public')->path($configurada);
                }
            } catch (\Throwable) {
                // Se usa la imagen predeterminada.
            }
        }

        return public_path($predeterminada);
    }

    private function imagenDataUri(?string $configurada, string $predeterminada): string
    {
        if ($configurada) {
            try {
                if (Storage::disk('public')->exists($configurada)) {
                    $contenido = Storage::disk('public')->get($configurada);
                    $extension = strtolower(pathinfo($configurada, PATHINFO_EXTENSION));
                    $mime = match ($extension) {
                        'jpg', 'jpeg' => 'image/jpeg',
                        'webp' => 'image/webp',
                        default => 'image/png',
                    };

                    return 'data:' . $mime . ';base64,' . base64_encode($contenido);
                }
            } catch (\Throwable) {
                // Continúa con el recurso predeterminado.
            }
        }

        $ruta = public_path($predeterminada);
        $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return is_file($ruta)
            ? 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($ruta))
            : '';
    }
}
