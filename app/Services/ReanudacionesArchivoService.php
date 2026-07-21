<?php

namespace App\Services;

use App\Models\ReanudacionLaboral;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use RuntimeException;
use ZipArchive;

class ReanudacionesArchivoService
{
    public function __construct(private readonly ReanudacionesService $documentos)
    {
    }

    /** @param array<string,mixed> $documento */
    public function guardarPdf(ReanudacionLaboral $registro, array $documento): ReanudacionLaboral
    {
        $ruta = $this->rutaBase($registro) . '/reanudacion-' . Str::slug($registro->persona_nombre) . '-' . $registro->id . '.pdf';
        $contenido = $this->documentos->renderPdf([$documento])->output();
        Storage::disk('local')->put($ruta, $contenido);

        $registro->forceFill(['archivo_pdf_path' => $ruta])->save();

        return $registro->refresh();
    }

    public function asegurarPdf(ReanudacionLaboral $registro): string
    {
        if ($registro->archivo_pdf_path && Storage::disk('local')->exists($registro->archivo_pdf_path)) {
            return Storage::disk('local')->path($registro->archivo_pdf_path);
        }

        $documento = $this->documentos->documentoDesdeRegistro($registro);
        $registro = $this->guardarPdf($registro, $documento);

        return Storage::disk('local')->path((string) $registro->archivo_pdf_path);
    }

    /** @param Collection<int,ReanudacionLaboral> $registros */
    public function crearZip(Collection $registros): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('La extensión ZIP de PHP no está disponible.');
        }

        $ruta = storage_path('app/temp/reanudaciones-' . Str::uuid() . '.zip');
        if (! is_dir(dirname($ruta))) {
            mkdir(dirname($ruta), 0775, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No fue posible crear el archivo ZIP.');
        }

        foreach ($registros->values() as $indice => $registro) {
            $pdf = $this->asegurarPdf($registro);
            $nombre = sprintf(
                '%s/%s-%s.pdf',
                Str::slug($registro->nivel_nombre ?: 'nivel'),
                str_pad((string) ($indice + 1), 3, '0', STR_PAD_LEFT),
                Str::slug($registro->persona_nombre)
            );
            $zip->addFile($pdf, $nombre);
        }

        $zip->close();

        return $ruta;
    }

    /** @param Collection<int,ReanudacionLaboral> $registros */
    public function crearWordMasivo(Collection $registros): string
    {
        $ruta = storage_path('app/temp/reanudaciones-' . Str::uuid() . '.docx');
        if (! is_dir(dirname($ruta))) {
            mkdir(dirname($ruta), 0775, true);
        }

        $word = new PhpWord();
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize(11);

        $word->addTitleStyle(1, ['bold' => true, 'size' => 12], ['alignment' => 'center']);
        $word->addParagraphStyle('derecha', ['alignment' => 'right', 'spaceAfter' => 120]);
        $word->addParagraphStyle('justificado', ['alignment' => 'both', 'lineHeight' => 1.25, 'spaceAfter' => 180]);
        $word->addParagraphStyle('centrado', ['alignment' => 'center', 'spaceAfter' => 120]);
        $word->addParagraphStyle('ccp', ['alignment' => 'left', 'spaceBefore' => 180, 'spaceAfter' => 0]);

        foreach ($registros->values() as $indice => $registro) {
            $snapshot = $registro->snapshot ?: [];
            $nivel = $registro->nivel_nombre ?: 'NIVEL EDUCATIVO';
            $escuela = data_get($snapshot, 'escuela.nombre', 'CENTRO UNIVERSITARIO MOCTEZUMA');
            $cct = data_get($snapshot, 'nivel.cct');

            $section = $word->addSection([
                'paperSize' => 'Letter',
                'marginTop' => Converter::cmToTwip(1.7),
                'marginBottom' => Converter::cmToTwip(1.5),
                'marginLeft' => Converter::cmToTwip(2.3),
                'marginRight' => Converter::cmToTwip(2.3),
            ]);

            $logo = public_path('logo.png');
            if (is_file($logo)) {
                $section->addImage($logo, [
                    'width' => 145,
                    'alignment' => 'center',
                    'wrappingStyle' => 'inline',
                ]);
            }

            $section->addText(Str::upper((string) $escuela), ['bold' => true, 'size' => 12], 'centrado');
            $subtitulo = Str::upper($nivel) . ($cct ? ' · C.C.T. ' . $cct : '');
            $section->addText($subtitulo, ['bold' => true, 'size' => 10], 'centrado');
            $section->addText('ASUNTO: REANUDACIÓN DE LABORES', ['bold' => true, 'size' => 10], 'derecha');
            $section->addText(
                'CIUDAD ALTAMIRANO, GRO., A ' . Str::upper($this->fechaLarga($registro->fecha_documento)) . '.',
                ['size' => 10],
                'derecha'
            );

            $destinatario = trim((string) ($registro->destinatario_nombre ?: data_get($snapshot, 'autoridades.destinatario_nombre')));
            $cargo = trim((string) ($registro->destinatario_cargo ?: data_get($snapshot, 'autoridades.destinatario_cargo')));
            $section->addText(Str::upper($destinatario ?: 'AUTORIDAD EDUCATIVA'), ['bold' => true]);
            $section->addText(Str::upper($cargo ?: 'PRESENTE'), ['bold' => true]);
            $section->addText('P R E S E N T E', ['bold' => true]);
            $section->addTextBreak(1);

            $tipo = match ($registro->tipo_reanudacion) {
                'receso' => 'el receso escolar',
                'invierno' => 'las vacaciones de invierno',
                'primavera' => 'el periodo vacacional de primavera',
                default => 'el periodo correspondiente',
            };

            $texto = 'Por este conducto, me permito informar que con fecha arriba señalada me presenté a reanudar mis labores, después de haber disfrutado ' . $tipo . ', en el ' . Str::upper($nivel) . ' del ' . $escuela . '.';
            $section->addText($texto, [], 'justificado');
            $section->addText('Sin otro particular, reciba un cordial saludo.', [], 'justificado');
            $section->addTextBreak(2);
            $section->addText('A T E N T A M E N T E', ['bold' => true], 'centrado');
            $section->addTextBreak(2);
            $section->addText(Str::upper($registro->persona_nombre), ['bold' => true], 'centrado');
            $section->addText(Str::upper(implode(' / ', $registro->cargos ?: [])), [], 'centrado');

            if (filled($registro->copias)) {
                foreach (preg_split('/\r\n|\r|\n/', (string) $registro->copias) ?: [] as $linea) {
                    if (trim($linea) !== '') {
                        $section->addText(trim($linea), ['size' => 8], 'ccp');
                    }
                }
            }

        }

        IOFactory::createWriter($word, 'Word2007')->save($ruta);

        return $ruta;
    }

    public function eliminar(ReanudacionLaboral $registro): void
    {
        if ($registro->archivo_pdf_path) {
            Storage::disk('local')->delete($registro->archivo_pdf_path);
        }
    }

    /** @param Collection<int,ReanudacionLaboral> $registros */
    public function eliminarLote(Collection $registros): void
    {
        $registros->each(fn (ReanudacionLaboral $registro) => $this->eliminar($registro));

        $primero = $registros->first();
        if ($primero) {
            Storage::disk('local')->deleteDirectory($this->rutaBase($primero));
        }
    }

    private function rutaBase(ReanudacionLaboral $registro): string
    {
        return 'reanudaciones-laborales/' . Str::slug($registro->ciclo_nombre ?: 'sin-ciclo') . '/' . $registro->lote_uuid;
    }

    private function fechaLarga($fecha): string
    {
        return Carbon::parse($fecha)->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
    }
}
