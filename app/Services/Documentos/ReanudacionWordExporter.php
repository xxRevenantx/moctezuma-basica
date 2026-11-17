<?php

namespace App\Services\Documentos;

use App\Models\Director;
use App\Models\ReanudacionLaboral;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Element\Section;
use RuntimeException;

/**
 * Exportación DOCX editable que reproduce las variantes de los PDF de reanudaciones.
 * Cada registro tiene su propia sección carta y utiliza el membrete histórico.
 */
class ReanudacionWordExporter
{
    /** @var array<string,Director|null> */
    private array $autoridadesLegadas = [];

    /** @var array<int,string> */
    private array $imagenesTemporales = [];

    /** @param Collection<int,ReanudacionLaboral> $registros */
    public function exportar(Collection $registros): string
    {
        $ruta = storage_path('app/temp/reanudaciones-' . Str::uuid() . '.docx');
        if (! is_dir(dirname($ruta))) {
            mkdir(dirname($ruta), 0775, true);
        }

        $word = new PhpWord();
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize(10);
        try {
            foreach ($registros as $registro) {
                $this->agregarOficio($word, $registro);
            }
            IOFactory::createWriter($word, 'Word2007')->save($ruta);
            return $ruta;
        } finally {
            foreach ($this->imagenesTemporales as $imagen) {
                @unlink($imagen);
            }
            $this->imagenesTemporales = [];
        }
    }

    private function agregarOficio(PhpWord $word, ReanudacionLaboral $registro): void
    {
        $d = $this->datos($registro);
        $esPrimaria = $d['nivel'] === 'primaria' && $registro->tipo_reanudacion !== 'receso';
        $esBachillerato = $d['nivel'] === 'bachillerato';
        $membrete = $this->membrete($registro);
        $margenSuperior = $membrete ? max(18, min(70, $membrete['margen_superior_mm'] + ($esPrimaria ? 8 : 4))) : 27;
        $s = $word->addSection([
            'paperSize' => 'Letter',
            'marginTop' => Converter::cmToTwip($margenSuperior / 10),
            'marginBottom' => Converter::cmToTwip($membrete ? 1.55 : 1.7),
            'marginLeft' => Converter::cmToTwip($esBachillerato ? 2.2 : 1.65),
            'marginRight' => Converter::cmToTwip($esBachillerato ? 2.2 : 1.65),
            'headerHeight' => 0,
            'footerHeight' => 0,
        ]);
        if ($membrete) {
            // La imagen completa de carta es la MISMA usada por DomPDF.
            $s->addHeader()->addWatermark($membrete['ruta'], [
                'width' => 612, 'height' => 792, 'marginTop' => 0,
                'marginLeft' => -0.65, 'wrappingStyle' => 'behind',
            ]);
        }
        if ($esBachillerato) {
            $this->bachillerato($s, $registro, $d, (bool) $membrete);
        } elseif ($esPrimaria) {
            $this->primaria($s, $registro, $d);
        } else {
            $this->basica($s, $registro, $d);
        }
    }

    /** @return array<string,mixed> */
    private function datos(ReanudacionLaboral $r): array
    {
        $snap = is_array($r->snapshot) ? $r->snapshot : [];
        $asignacion = $r->personaNivel;
        $persona = $asignacion?->persona ?: $r->persona;
        $nivel = $r->nivel;
        $director = $nivel?->director;
        $supervisor = $nivel?->supervisor;
        $autoridades = (array) data_get($snap, 'autoridades', []);
        // Antes de esta mejora el snapshot no conservaba al Director de
        // Administración ni a la Dirección de Magisterio. Recuperarlos por su
        // identificador evita colocar por error al supervisor como destinatario.
        $administracion = $this->autoridadLegada('director-general-administracion');
        $magisterio = $this->autoridadLegada('director-magisterio-estatal');
        $delegado = $this->autoridadLegada('delegado-servicios-educativos-tierra-caliente');
        $nombre = $this->mayus(implode(' ', array_filter([
            data_get($snap, 'persona.nombre', $persona?->nombre),
            data_get($snap, 'persona.apellido_paterno', $persona?->apellido_paterno),
            data_get($snap, 'persona.apellido_materno', $persona?->apellido_materno),
        ])));
        $nombre = $nombre ?: $this->mayus((string) $r->persona_nombre);
        $titulo = trim((string) data_get($snap, 'persona.titulo', $persona?->titulo));
        $cargo = collect(data_get($snap, 'cargos', $r->cargos ?: []))
            ->filter()->map(fn ($c) => $this->mayus((string) $c))->implode(', ');
        $nombreDirector = function ($clave, $modelo) use ($autoridades) {
            $valor = data_get($autoridades, $clave);
            return $valor !== null ? $this->mayus((string) $valor) : $this->mayus(implode(' ', array_filter([
                $modelo?->titulo, $modelo?->nombre, $modelo?->apellido_paterno, $modelo?->apellido_materno,
            ])));
        };
        return [
            'nivel' => Str::lower((string) ($r->nivel_slug ?: data_get($snap, 'nivel.slug', ''))),
            'nombre' => $nombre,
            'nombre_titulado' => $this->mayus(trim(($titulo === 'C.' ? '' : $titulo . ' ') . $nombre)),
            'rfc' => $this->valor(data_get($snap, 'persona.rfc', $persona?->rfc)),
            'curp' => $this->valor(data_get($snap, 'persona.curp', $persona?->curp)),
            'ingreso_seg' => data_get($snap, 'empleo.ingreso_seg', $asignacion?->ingreso_seg),
            'ingreso_sep' => data_get($snap, 'empleo.ingreso_sep', $asignacion?->ingreso_sep),
            'ingreso_ct' => data_get($snap, 'empleo.ingreso_ct', $asignacion?->ingreso_ct),
            'cargo' => $cargo ?: '---------',
            'directivo' => (bool) $r->es_directivo,
            'escuela' => $this->valor(data_get($snap, 'escuela.nombre', 'CENTRO UNIVERSITARIO MOCTEZUMA A.C.')),
            'lema' => trim((string) data_get($snap, 'escuela.lema', '')),
            'ciudad' => $this->valor(data_get($snap, 'escuela.ciudad', 'ALTAMIRANO')),
            'municipio' => $this->valor(data_get($snap, 'escuela.municipio')),
            'cct' => $this->valor(data_get($snap, 'nivel.cct', $nivel?->cct)),
            'zona' => $this->valor(data_get($autoridades, 'supervisor_zona_escolar', $supervisor?->zona_escolar)),
            'director' => $nombreDirector('director_nombre', $director),
            'director_rfc' => $this->valor(data_get($autoridades, 'director_rfc', $director?->rfc)),
            'director_curp' => $this->valor(data_get($autoridades, 'director_curp', $director?->curp)),
            'supervisor' => $nombreDirector('supervisor_nombre', $supervisor),
            'supervisor_cargo' => $this->valor(data_get($autoridades, 'supervisor_cargo', $supervisor?->cargo)),
            'supervisor_rfc' => $this->valor(data_get($autoridades, 'supervisor_rfc', $supervisor?->rfc)),
            'supervisor_curp' => $this->valor(data_get($autoridades, 'supervisor_curp', $supervisor?->curp)),
            'delegado' => $this->mayus((string) data_get($autoridades, 'delegado_nombre', $nombreDirector('delegado_nombre', $delegado))),
            'delegado_cargo' => $this->mayus((string) data_get($autoridades, 'delegado_cargo', $delegado?->cargo)),
            'administracion' => $this->mayus((string) data_get($autoridades, 'administracion_nombre', $nombreDirector('administracion_nombre', $administracion))),
            'administracion_cargo' => $this->mayus((string) data_get($autoridades, 'administracion_cargo', $administracion?->cargo)),
            'magisterio' => $this->mayus((string) data_get($autoridades, 'magisterio_nombre', $nombreDirector('magisterio_nombre', $magisterio))),
            'magisterio_cargo' => $this->mayus((string) data_get($autoridades, 'magisterio_cargo', $magisterio?->cargo)),
            'destinatario' => $this->mayus((string) $r->destinatario_nombre),
            'destinatario_cargo' => $this->mayus((string) $r->destinatario_cargo),
            'ciclo' => (string) data_get($snap, 'ciclo_citado.nombre', $r->ciclo_nombre),
            'copias' => trim((string) $r->copias),
        ];
    }

    /** @param array<string,mixed> $d */
    private function primaria(Section $s, ReanudacionLaboral $r, array $d): void
    {
        $derecha = ['alignment' => 'right', 'spaceAfter' => 0, 'lineHeight' => 1.05];
        $s->addText('ASUNTO: AVISO DE REANUDACIÓN DE LABORES', ['name' => 'Times New Roman', 'size' => 11.2, 'bold' => true], $derecha);
        $s->addText('Cd. Altamirano, Gro., ' . $this->fecha($r->fecha_documento) . '.', ['name' => 'Times New Roman', 'size' => 10.5], $derecha);
        $s->addText($d['lema'] === '' ? '' : '"' . $d['lema'] . '"', ['name' => 'Times New Roman', 'size' => 10.3], ['alignment' => 'right', 'spaceAfter' => 150]);

        $tablaAutoridad = $s->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $tablaAutoridad->addRow();
        $this->autoridad($tablaAutoridad->addCell(4850), $d['administracion'], $d['administracion_cargo']);
        $tablaAutoridad->addCell(4450)->addText('');
        $tablaAutoridad->addRow();
        $tablaAutoridad->addCell(4850)->addText('');
        $this->autoridad($tablaAutoridad->addCell(4450), "AT'N. " . $d['magisterio'], $d['magisterio_cargo']);

        $p = $s->addTextRun(['alignment' => 'both', 'indentation' => ['firstLine' => 500], 'lineHeight' => 1.1, 'spaceBefore' => 100, 'spaceAfter' => 65]);
        $p->addText('EL (A) QUE SUSCRIBE C.  ', ['size' => 9.3]);
        $p->addText($d['nombre_titulado'], ['size' => 9.3, 'bold' => true, 'underline' => 'single']);
        $p->addText(', ME PERMITO INFORMAR QUE A PARTIR DE ESTA FECHA, ME PRESENTÉ A REANUDAR MIS LABORES, DESPUÉS DE HABER DISFRUTADO ', ['size' => 9.3]);
        $p->addText($this->periodo($r, 'primaria'), ['size' => 9.3, 'bold' => true, 'underline' => 'single']);
        $p->addText(', CORRESPONDIENTE AL CICLO ESCOLAR ', ['size' => 9.3]);
        $p->addText($d['ciclo'] . '.', ['size' => 9.3, 'bold' => true]);

        $s->addText('PARA LO CUAL PROPORCIONO LOS SIGUIENTES DATOS:', ['size' => 9.3], ['spaceAfter' => 85]);
        $this->tablaDatos($s, [
            'NOMBRE' => $d['nombre'], 'FILIACIÓN' => $d['rfc'], 'CURP' => $d['curp'],
            'CLAVE PRESUPUESTAL' => 'S/C',
            'FECHA DE INGRESO A LA SEG' => $this->fechaIngreso($d['ingreso_seg']),
            'FECHA DE INGRESO AL C.T.' => $this->fechaIngreso($d['ingreso_ct']),
            'CATEGORÍA' => $d['cargo'], 'NOMBRE DEL CENTRO DE TRABAJO' => $d['escuela'],
            'C.C.T.' => $d['cct'], 'LOCALIDAD' => 'CD. ' . $d['ciudad'],
            'MUNICIPIO' => $d['municipio'], 'FUNCIÓN QUE DESEMPEÑA' => $d['cargo'],
            'REGIÓN' => 'TIERRA CALIENTE', 'ZONA ESCOLAR' => $d['zona'],
        ], 4700, 5600, 9.0);
        $s->addText('SIN OTRO ASUNTO QUE TRATAR APROVECHO LA OCASIÓN PARA ENVIARLE UN CORDIAL SALUDO.',
            ['size' => 9.0], ['spaceBefore' => 110, 'spaceAfter' => 105]);
        $this->firmas($s, $d, true);
        $this->copias($s, $d['copias'], 'primaria');
    }

    /** @param array<string,mixed> $d */
    private function basica(Section $s, ReanudacionLaboral $r, array $d): void
    {
        $s->addText('ASUNTO: REANUDACIÓN DE LABORES', ['size' => 11, 'bold' => true], ['alignment' => 'right', 'spaceAfter' => 0]);
        $s->addText('CD. ALTAMIRANO, GRO., A ' . $this->mayus($this->fecha($r->fecha_documento)) . '.',
            ['size' => 10], ['alignment' => 'right', 'spaceAfter' => 0]);
        $s->addText($d['lema'] === '' ? '' : '"' . $d['lema'] . '"', ['size' => 9.3], ['alignment' => 'right', 'spaceAfter' => 155]);
        $s->addText($d['delegado'] ?: $d['destinatario'], ['bold' => true, 'size' => 10.2], ['spaceAfter' => 0]);
        $s->addText($d['delegado_cargo'] ?: $d['destinatario_cargo'], ['size' => 9.3], ['spaceAfter' => 0]);
        $s->addText('P R E S E N T E', ['size' => 10, 'bold' => true], ['spaceAfter' => 165]);

        $p = $s->addTextRun(['alignment' => 'both', 'indentation' => ['firstLine' => 410], 'lineHeight' => 1.14, 'spaceAfter' => 95]);
        $p->addText('EL (A) QUE SUSCRIBE C.  ', ['size' => 9.4]);
        $p->addText($d['nombre_titulado'], ['size' => 9.4, 'bold' => true, 'underline' => 'single']);
        $p->addText(', SE DIRIGE A USTED PARA INFORMARLE QUE CON FECHA ARRIBA SEÑALADA, ME PRESENTÉ A REANUDAR LABORES, DESPUÉS DE HABER DISFRUTADO ', ['size' => 9.4]);
        $p->addText($this->periodo($r, $d['nivel']), ['size' => 9.4, 'bold' => true, 'underline' => 'single']);
        $p->addText(', CORRESPONDIENTE AL CICLO ESCOLAR ', ['size' => 9.4]);
        $p->addText($d['ciclo'] . '.', ['size' => 9.4, 'bold' => true]);
        $s->addText('PARA LO CUAL PROPORCIONO LOS SIGUIENTES DATOS:', ['size' => 9.2], ['spaceAfter' => 90]);
        $pre = $d['nivel'] === 'preescolar' || ($d['nivel'] === 'primaria' && $r->tipo_reanudacion === 'receso');
        $datos = [
            ($pre ? 'NOMBRE COMPLETO' : 'NOMBRE') => $d['nombre'],
            'FILIACIÓN' => $d['rfc'], 'CURP' => $d['curp'],
            'CLAVE (S) PRESUPUESTAL (ES)' => 'S/C', 'CARGO QUE DESEMPEÑA' => $d['cargo'],
            'FECHA DE INGRESO A LA SEP' => $this->fechaIngreso($d['ingreso_sep'], $pre ? 'DD-MM-YYYY' : 'D [DE] MMMM [DEL] YYYY'),
            ($pre ? 'FECHA DE INGRESO AL CENTRO DE TRABAJO' : 'FECHA DE INGRESO AL C.T.') => $this->fechaIngreso($d['ingreso_ct'], $pre ? 'DD-MM-YYYY' : 'D [DE] MMMM [DEL] YYYY'),
            'NOMBRE DEL C.T.' => $d['escuela'], 'C.C.T.' => $d['cct'],
            'UBICACIÓN' => 'FRANCISCO I. MADERO OTE. 800 COL. ESQUIPULAS. CD. ALTAMIRANO, GRO.',
        ];
        $this->tablaDatos($s, $datos, 4750, 4550, 9.1);
        $s->addText('SIN OTRO PARTICULAR, APROVECHO LA OCASIÓN PARA ENVIARLE UN AFECTUOSO SALUDO.',
            ['size' => 9.2], ['spaceBefore' => 135, 'spaceAfter' => 110]);
        $this->firmas($s, $d, false);
        $this->copias($s, $d['copias'], $d['nivel']);
    }

    /** @param array<string,mixed> $d */
    private function bachillerato(Section $s, ReanudacionLaboral $r, array $d, bool $conMembrete): void
    {
        if (! $conMembrete) {
            $s->addText($d['escuela'], ['size' => 14, 'bold' => true, 'color' => '006492'], ['spaceAfter' => 40]);
            $s->addText('BACHILLERATO · C.C.T. ' . $d['cct'] . ' · CICLO ESCOLAR ' . $r->ciclo_nombre,
                ['size' => 8.5, 'color' => '52627A'], ['spaceAfter' => 300]);
        }
        $s->addText('ASUNTO: REANUDACIÓN DE LABORES', ['size' => 10, 'bold' => true], ['alignment' => 'right', 'spaceAfter' => 0]);
        $s->addText('CIUDAD ALTAMIRANO, GRO., A ' . $this->mayus($this->fecha($r->fecha_documento)) . '.',
            ['size' => 9.4], ['alignment' => 'right', 'spaceAfter' => 300]);
        $s->addText($d['destinatario'], ['size' => 10, 'bold' => true], ['spaceAfter' => 0]);
        $s->addText($d['destinatario_cargo'], ['size' => 9.5], ['spaceAfter' => 0]);
        $s->addText('P R E S E N T E', ['size' => 10], ['spaceAfter' => 300]);
        $p = $s->addTextRun(['alignment' => 'both', 'lineHeight' => 1.5, 'spaceAfter' => 180]);
        $p->addText('Por este conducto, me permito informar que con fecha arriba señalada me presenté a reanudar mis labores, después de haber disfrutado ', ['size' => 11]);
        $p->addText($this->periodo($r, 'bachillerato'), ['size' => 11, 'bold' => true, 'underline' => 'single']);
        $p->addText(', correspondiente al ciclo escolar ' . $d['ciclo'] . ', incorporándome a las actividades correspondientes del nivel Bachillerato del ' . $d['escuela'] . '.', ['size' => 11]);
        $s->addText('Sin otro particular, reciba un cordial saludo.', ['size' => 11], ['spaceAfter' => 440]);
        $s->addText('A T E N T A M E N T E', ['size' => 10, 'bold' => true], ['alignment' => 'center', 'spaceAfter' => 500]);
        $s->addText('____________________________________', ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 25]);
        $s->addText($d['nombre_titulado'], ['size' => 10, 'bold' => true], ['alignment' => 'center', 'spaceAfter' => 40]);
        $s->addText($d['cargo'], ['size' => 9, 'color' => '44536A'], ['alignment' => 'center']);
        $this->copias($s, $d['copias'], 'bachillerato');
    }

    /** @param array<string,string> $datos */
    private function tablaDatos(Section $seccion, array $datos, int $anchoEtiqueta, int $anchoValor, float $size): void
    {
        $tabla = $seccion->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        foreach ($datos as $etiqueta => $valor) {
            $tabla->addRow();
            $tabla->addCell($anchoEtiqueta)->addText($etiqueta . ':', ['name' => 'Arial', 'size' => $size, 'bold' => true],
                ['spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.0]);
            $tabla->addCell($anchoValor, ['borderBottomSize' => 4, 'borderBottomColor' => '505050'])
                ->addText($this->mayus((string) $valor), ['name' => 'Arial', 'size' => $size],
                    ['spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.0]);
        }
    }

    /** @param array<string,mixed> $d */
    private function firmas(Section $seccion, array $d, bool $incluyeRfcCurp): void
    {
        $t = $seccion->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $t->addRow();
        $izq = $t->addCell(5150);
        $der = $t->addCell(5150);
        $est = ['name' => 'Arial', 'size' => 7.7, 'bold' => true];
        $p = ['alignment' => 'center', 'spaceAfter' => 0, 'lineHeight' => 1.0];
        $izq->addText('ATENTAMENTE', $est, $p);
        $der->addText('VO.BO.', $est, $p);
        $der->addText($d['directivo'] ? 'JEFE INMEDIATO' : ($incluyeRfcCurp ? 'DIRECTORA' : 'DIRECTOR(A)'), $est, $p);
        if ($d['directivo'] && $incluyeRfcCurp) {
            $der->addText($d['supervisor_cargo'], $est, $p);
        }
        $izq->addTextBreak(3, ['size' => 9]);
        $der->addTextBreak($d['directivo'] && $incluyeRfcCurp ? 2 : 3, ['size' => 9]);
        $izq->addText('_____________________________', $est, $p);
        $izq->addText($d['nombre_titulado'], $est, $p);
        if ($incluyeRfcCurp) {
            $izq->addText('RFC: ' . $d['rfc'], $est, $p);
            $izq->addText('CURP: ' . $d['curp'], $est, $p);
        }
        $der->addText('_____________________________', $est, $p);
        $der->addText($d['directivo'] ? $d['supervisor'] : $d['director'], $est, $p);
        if ($incluyeRfcCurp) {
            $der->addText('RFC: ' . ($d['directivo'] ? $d['supervisor_rfc'] : $d['director_rfc']), $est, $p);
            $der->addText('CURP: ' . ($d['directivo'] ? $d['supervisor_curp'] : $d['director_curp']), $est, $p);
        }
    }

    private function autoridad($celda, string $nombre, string $cargo): void
    {
        $celda->addText($nombre, ['name' => 'Times New Roman', 'size' => 9.2, 'bold' => true], ['spaceAfter' => 0, 'lineHeight' => 1.0]);
        // Mantener saltos explícitos y controlar anchura del cargo de la autoridad.
        $cargo = preg_replace('/\s+(?=DESARROLLO |PERSONAL |GOBIERNO |EDO\. |CHILPANCINGO)/u', "\n", $cargo) ?? $cargo;
        foreach (preg_split('/\r\n|\r|\n/', $cargo) ?: [] as $linea) {
            if (trim($linea) !== '') {
                $celda->addText(trim($linea), ['name' => 'Times New Roman', 'size' => 8.8], ['spaceAfter' => 0, 'lineHeight' => 1.0]);
            }
        }
    }

    private function copias(Section $seccion, string $copias, string $nivel): void
    {
        if ($copias === '' && $nivel === 'primaria') {
            // Es la leyenda predeterminada del mismo PDF (sin modificarla).
            $copias = implode("\n", [
                'C.C.P. JOSÉ ZAMORA ÁVILA, JEFE DE DEPARTAMENTO DE PRIMARIA ESTATAL',
                'C.C.P. LIC. SABINO FLORES LEÒN. JEFE REGIONAL 01. REGION TIERRA CALIENTE. CD. ALTAMIRANO, GRO',
                'C.C.P. PROFR (A) ADELFO MARTINEZ MARTINEZ - SUPERVISOR (A) ZONA ESCOLAR 030. CD ALTAMIRANO, GRO.',
                'C.C.P. EL INTERESADO',
            ]);
        }
        if (trim($copias) === '') {
            return;
        }
        $copias = preg_replace('/\s+(?=C\.?\s*C\.?\s*P\.?\s+)/iu', "\n", $copias) ?? $copias;
        foreach (preg_split('/\r\n|\r|\n/u', $copias) ?: [] as $linea) {
            if (trim($linea) !== '') {
                $seccion->addText(trim($linea), ['name' => 'Arial', 'size' => $nivel === 'primaria' ? 6 : 6.4],
                    ['spaceAfter' => 0, 'spaceBefore' => 0, 'lineHeight' => 1]);
            }
        }
    }

    private function autoridadLegada(string $identificador): ?Director
    {
        if (! array_key_exists($identificador, $this->autoridadesLegadas)) {
            $this->autoridadesLegadas[$identificador] = Director::query()
                ->where('identificador', $identificador)->first();
        }
        return $this->autoridadesLegadas[$identificador];
    }

    private function periodo(ReanudacionLaboral $registro, string $nivel): string
    {
        return match ($registro->tipo_reanudacion) {
            'invierno' => $nivel === 'preescolar' ? 'EL PERIODO VACACIONAL DE INVIERNO' : 'LAS VACACIONES DE INVIERNO',
            'primavera' => 'EL PERIODO VACACIONAL DE PRIMAVERA',
            'receso' => 'DEL RECESO ESCOLAR',
            default => 'EL PERIODO CORRESPONDIENTE',
        };
    }

    private function fecha($v, string $fmt = 'D [de] MMMM [de] YYYY'): string
    {
        return Carbon::parse($v)->locale('es')->isoFormat($fmt);
    }

    private function fechaIngreso($v, string $fmt = 'DD [DE] MMMM [DEL] YYYY'): string
    {
        return $v ? $this->mayus($this->fecha($v, $fmt)) : '---------';
    }

    private function mayus(string $v): string
    {
        return mb_strtoupper(trim($v), 'UTF-8');
    }

    private function valor($v): string
    {
        $v = trim((string) ($v ?? ''));
        return $v === '' ? '---------' : $this->mayus($v);
    }

    /** Word no puede incrustar de forma portátil imágenes WebP. */
    private function imagenWord(string $ruta): string
    {
        if (! preg_match('/\.webp$/i', $ruta)) {
            return $ruta;
        }
        if (! function_exists('imagecreatefromwebp') || ! function_exists('imagepng')) {
            throw new RuntimeException('Para exportar membretes WebP a Word se requiere la extensión GD de PHP.');
        }
        $imagen = @imagecreatefromwebp($ruta);
        if ($imagen === false) {
            throw new RuntimeException('No fue posible leer la imagen WebP del membrete.');
        }
        $temporal = storage_path('app/temp/membrete-' . Str::uuid() . '.png');
        if (! is_dir(dirname($temporal))) {
            mkdir(dirname($temporal), 0775, true);
        }
        imagealphablending($imagen, false);
        imagesavealpha($imagen, true);
        $guardada = imagepng($imagen, $temporal);
        imagedestroy($imagen);
        if (! $guardada) {
            throw new RuntimeException('No fue posible preparar el membrete PNG para Word.');
        }
        $this->imagenesTemporales[] = $temporal;
        return $temporal;
    }

    /** @return array{ruta:string,margen_superior_mm:float}|null */
    private function membrete(ReanudacionLaboral $r): ?array
    {
        $m = data_get($r->snapshot ?: [], 'membrete');
        $path = is_array($m) ? trim((string) ($m['archivo_path'] ?? '')) : '';
        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }
        return [
            'ruta' => $this->imagenWord(Storage::disk('public')->path($path)),
            'margen_superior_mm' => (float) ($m['margen_superior_mm'] ?? 32),
        ];
    }
}
