<?php

namespace App\Exports;

use App\Support\PromedioExcel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PromediosGeneralesResumenSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    private array $filas = [];

    /** @var array<int, int> */
    private array $filasTitulo = [];

    /** @var array<int, int> */
    private array $filasEncabezado = [];

    public function __construct(
        protected string $nivelNombre,
        protected string $nivelSlug,
        protected bool $esBachillerato,
        protected array $resumen,
        protected array $filtros = [],
    ) {
        $this->prepararFilas();
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function array(): array
    {
        return $this->filas;
    }

    private function prepararFilas(): void
    {
        $this->filas[] = ['CONCENTRADO FINAL DE ' . mb_strtoupper($this->nivelNombre)];
        $this->filas[] = ['Exportación generada el ' . now()->format('d/m/Y H:i')];
        $this->filas[] = [];

        $this->filasTitulo[] = count($this->filas) + 1;
        $this->filas[] = ['FILTROS APLICADOS'];
        $this->filasEncabezado[] = count($this->filas) + 1;
        $this->filas[] = ['Filtro', 'Valor'];

        foreach ($this->filtros as $nombre => $valor) {
            $this->filas[] = [$nombre, $valor ?: 'Todos'];
        }

        $this->filas[] = [];
        $this->filasTitulo[] = count($this->filas) + 1;
        $this->filas[] = ['RESUMEN GENERAL'];
        $this->filasEncabezado[] = count($this->filas) + 1;
        $this->filas[] = ['Indicador', 'Valor'];

        $this->filas[] = ['Total de alumnos', $this->resumen['total_alumnos'] ?? 0];
        $this->filas[] = ['Promedio general', $this->valor($this->resumen['promedio_general'] ?? null)];
        $this->filas[] = ['Acreditados', $this->resumen['aprobados'] ?? 0];
        $this->filas[] = ['En riesgo', $this->resumen['riesgo'] ?? 0];
        $this->filas[] = ['Incompletos', $this->resumen['incompletos'] ?? 0];
        $this->filas[] = ['Decisiones pendientes', $this->resumen['pendientes_decision'] ?? 0];
        $this->filas[] = ['Mejor promedio', $this->valor($this->resumen['mejor_promedio'] ?? null)];
        $this->filas[] = ['Mejor alumno', $this->resumen['mejor_alumno'] ?? 'Sin datos'];

        $this->filas[] = [];
        $this->filasTitulo[] = count($this->filas) + 1;
        $this->filas[] = ['FÓRMULA APLICADA'];

        if ($this->nivelSlug === 'primaria') {
            $this->filas[] = ['Promedio final de campo', 'PROMEDIO(P1, P2, P3), truncado a un decimal para establecer el valor oficial'];
            $this->filas[] = ['Promedio general', 'Suma de los cuatro promedios oficiales de campo / 4'];
            $this->filas[] = ['Promoción sugerida', '1.º por conclusión del grado; 2.º a 6.º requieren los cuatro campos con mínimo 6.0'];
        } elseif ($this->nivelSlug === 'secundaria') {
            $this->filas[] = ['Promedio anual por materia', 'PROMEDIO(P1, P2, P3) con precisión completa'];
            $this->filas[] = ['Promedio general', 'PROMEDIO de los promedios anuales precisos de las materias participantes'];
            $this->filas[] = ['Tutoría', 'Se muestra, pero no participa en promedio, lugares ni promoción'];
        } elseif ($this->esBachillerato) {
            $this->filas[] = ['Promedio semestral', 'PROMEDIO de los parciales numéricos del semestre'];
        } else {
            $this->filas[] = ['Promedio anual', 'PROMEDIO de las evaluaciones numéricas configuradas'];
        }

        $nota = $this->nivelSlug === 'primaria'
            ? 'Los vacíos y claves especiales no se convierten en cero. Cada promedio final de campo se establece con un decimal truncado antes de calcular el promedio general.'
            : 'Los vacíos y claves especiales no se convierten en cero. Los cálculos intermedios no se truncan; el truncamiento a un decimal se aplica únicamente al presentar.';

        $this->filas[] = ['Nota', $nota];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $hoja = $event->sheet->getDelegate();
                $ultimaFila = $hoja->getHighestRow();

                $hoja->mergeCells('A1:D1');
                $hoja->mergeCells('A2:D2');
                $hoja->getRowDimension(1)->setRowHeight(30);
                $hoja->getRowDimension(2)->setRowHeight(22);

                $hoja->getColumnDimension('A')->setWidth(30);
                $hoja->getColumnDimension('B')->setWidth(80);
                $hoja->getColumnDimension('C')->setWidth(18);
                $hoja->getColumnDimension('D')->setWidth(18);

                $hoja->getStyle('A1:D1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006492']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $hoja->getStyle('A2:D2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '475569']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                foreach ($this->filasTitulo as $fila) {
                    $hoja->mergeCells("A{$fila}:D{$fila}");
                    $hoja->getStyle("A{$fila}:D{$fila}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '88AC2E']],
                    ]);
                }

                foreach ($this->filasEncabezado as $fila) {
                    $hoja->getStyle("A{$fila}:B{$fila}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
                    ]);
                }

                $hoja->getStyle("A1:D{$ultimaFila}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $hoja->freezePane('A4');
                $hoja->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
            },
        ];
    }

    private function valor(mixed $valor): string
    {
        if ($valor === null || $valor === '' || ! is_numeric($valor)) {
            return '—';
        }

        return PromedioExcel::formatear($valor, 1, '—');
    }
}
