<?php

namespace App\Exports;

use App\Support\PromedioExcel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class PromediosMateriasConcentradoSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    private array $bloquesMapa = [];
    private array $camposMapa = [];
    private array $materiasMapa = [];
    private array $promediosMapa = [];
    private int $ultimaColumnaIndice = 1;

    public function __construct(protected array $reporte)
    {
        //
    }

    public function title(): string
    {
        return 'Concentrado Matutino';
    }

    public function array(): array
    {
        $bloques = collect($this->reporte['bloques'] ?? []);
        $filas = [];
        $filas[] = ['PROMEDIO DE LOS TRES PERIODOS POR MATERIA'];
        $filas[] = [
            ($this->reporte['nivel']['nombre'] ?? 'Nivel') . ' · Ciclo escolar ' . ($this->reporte['ciclo']['texto'] ?? '—'),
        ];
        $filas[] = ['TURNO', 'CAMPOS FORMATIVOS'];

        $filaBloques = [''];
        $filaCampos = [''];
        $filaMaterias = [''];
        $filaMatutino = ['MATUTINO'];
        $filaEscuela = ['PROM. GRAL. DE LA ESCUELA'];

        $columna = 2;

        foreach ($bloques as $bloque) {
            $inicioBloque = $columna;

            foreach (collect($bloque['campos'] ?? []) as $campo) {
                $inicioCampo = $columna;

                foreach (collect($campo['materias'] ?? []) as $materia) {
                    $filaBloques[] = $bloque['titulo'] ?? 'Bloque';
                    $filaCampos[] = $campo['nombre'] ?? 'Sin campo formativo';
                    $filaMaterias[] = $materia['materia'] ?? 'Materia';
                    $filaMatutino[] = $this->valor($materia['promedio_metodo_a'] ?? null);
                    $filaEscuela[] = $this->valor($materia['promedio_metodo_b'] ?? null);

                    $this->materiasMapa[] = [
                        'columna' => $columna,
                        'fondo' => $campo['color_fondo'] ?? '#E2E8F0',
                        'texto' => $campo['color_texto'] ?? '#334155',
                    ];

                    $columna++;
                }

                $finCampo = $columna - 1;
                if ($finCampo >= $inicioCampo) {
                    $this->camposMapa[] = [
                        'inicio' => $inicioCampo,
                        'fin' => $finCampo,
                        'fondo' => $campo['color_fondo'] ?? '#E2E8F0',
                        'texto' => $campo['color_texto'] ?? '#334155',
                    ];
                }
            }

            $filaBloques[] = $bloque['titulo'] ?? 'Bloque';
            $filaCampos[] = 'PROM. GRAL.';
            $filaMaterias[] = 'PROMEDIO';
            $filaMatutino[] = $this->valor($bloque['promedio_general'] ?? null);
            $filaEscuela[] = $this->valor($bloque['promedio_general'] ?? null);
            $this->promediosMapa[] = ['columna' => $columna];
            $columna++;

            $this->bloquesMapa[] = [
                'inicio' => $inicioBloque,
                'fin' => $columna - 1,
            ];
        }

        $this->ultimaColumnaIndice = max(1, $columna - 1);

        $filas[] = $filaBloques;
        $filas[] = $filaCampos;
        $filas[] = $filaMaterias;
        $filas[] = $filaMatutino;
        $filas[] = $filaEscuela;
        $filas[] = [$this->reporte['nota'] ?? ''];

        return $filas;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $hoja = $event->sheet->getDelegate();
                $ultimaColumna = Coordinate::stringFromColumnIndex($this->ultimaColumnaIndice);

                $hoja->mergeCells("A1:{$ultimaColumna}1");
                $hoja->mergeCells("A2:{$ultimaColumna}2");
                $hoja->mergeCells('A3:A6');

                if ($this->ultimaColumnaIndice >= 2) {
                    $hoja->mergeCells("B3:{$ultimaColumna}3");
                }

                foreach ($this->bloquesMapa as $bloque) {
                    $inicio = Coordinate::stringFromColumnIndex($bloque['inicio']);
                    $fin = Coordinate::stringFromColumnIndex($bloque['fin']);
                    $hoja->mergeCells("{$inicio}4:{$fin}4");
                }

                foreach ($this->camposMapa as $campo) {
                    $inicio = Coordinate::stringFromColumnIndex($campo['inicio']);
                    $fin = Coordinate::stringFromColumnIndex($campo['fin']);
                    $hoja->mergeCells("{$inicio}5:{$fin}5");
                    $hoja->getStyle("{$inicio}5:{$fin}6")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $this->rgb($campo['fondo'])],
                        ],
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => $this->rgb($campo['texto'])],
                        ],
                    ]);
                }

                foreach ($this->promediosMapa as $promedio) {
                    $col = Coordinate::stringFromColumnIndex($promedio['columna']);
                    $hoja->mergeCells("{$col}5:{$col}6");
                    $hoja->getStyle("{$col}5:{$col}8")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'ECFCCB'],
                        ],
                        'font' => ['bold' => true, 'color' => ['rgb' => '365314']],
                    ]);
                }

                foreach ($this->materiasMapa as $materia) {
                    $col = Coordinate::stringFromColumnIndex($materia['columna']);
                    $hoja->getStyle("{$col}6")->getAlignment()->setTextRotation(90);
                    $hoja->getColumnDimension($col)->setWidth(6);
                }

                $hoja->mergeCells("A9:{$ultimaColumna}9");
                $hoja->getStyle("A1:{$ultimaColumna}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006492']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $hoja->getStyle("A2:{$ultimaColumna}2")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '14532D']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCFCE7']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $hoja->getStyle("A3:{$ultimaColumna}6")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $hoja->getStyle("A3:{$ultimaColumna}6")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $hoja->getStyle("A3:{$ultimaColumna}4")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF9C3']],
                ]);
                $hoja->getStyle("A7:{$ultimaColumna}7")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                    'font' => ['bold' => true],
                ]);
                $hoja->getStyle("A8:{$ultimaColumna}8")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9F99D']],
                    'font' => ['bold' => true],
                ]);
                $hoja->getStyle("A9:{$ultimaColumna}9")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEFCE8']],
                    'font' => ['bold' => true],
                ]);
                $hoja->getStyle("A1:{$ultimaColumna}9")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '475569'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $hoja->getColumnDimension('A')->setWidth(24);
                $hoja->getRowDimension(6)->setRowHeight(115);
                $hoja->getRowDimension(7)->setRowHeight(34);
                $hoja->getRowDimension(8)->setRowHeight(40);
                $hoja->freezePane('B7');
                $hoja->setAutoFilter("A6:{$ultimaColumna}8");

                $hoja->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize($this->ultimaColumnaIndice > 20 ? PageSetup::PAPERSIZE_A3 : PageSetup::PAPERSIZE_LEGAL)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $hoja->getPageMargins()->setTop(0.3)->setBottom(0.3)->setLeft(0.2)->setRight(0.2);
            },
        ];
    }

    private function valor(mixed $valor): string
    {
        return PromedioExcel::formatear($valor, 1, '—');
    }

    private function rgb(string $hex): string
    {
        return strtoupper(ltrim($hex, '#'));
    }
}
