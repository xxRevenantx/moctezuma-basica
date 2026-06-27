<?php

namespace App\Exports;

use App\Support\PromedioExcel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PromediosMateriasResumenSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    protected array $filasTitulo = [];
    protected array $filasEncabezado = [];

    public function __construct(
        protected string $nivelNombre,
        protected array $resumen,
        protected Collection $grados,
        protected Collection $grupos,
        protected array $filtros = [],
    ) {
        //
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function array(): array
    {
        $filas = [];

        $filas[] = ['PROMEDIOS ANUALES POR MATERIA - ' . mb_strtoupper($this->nivelNombre)];
        $filas[] = ['Exportación generada el ' . now()->format('d/m/Y H:i')];
        $filas[] = [];

        $this->filasTitulo[] = count($filas) + 1;
        $filas[] = ['FILTROS APLICADOS'];
        $this->filasEncabezado[] = count($filas) + 1;
        $filas[] = ['Filtro', 'Valor'];

        foreach ($this->filtros as $nombre => $valor) {
            $filas[] = [$nombre, $valor ?: 'Todos'];
        }

        $filas[] = [];
        $this->filasTitulo[] = count($filas) + 1;
        $filas[] = ['RESUMEN GENERAL'];
        $this->filasEncabezado[] = count($filas) + 1;
        $filas[] = ['Indicador', 'Valor'];
        $filas[] = ['Total de alumnos', $this->resumen['total_alumnos'] ?? 0];
        $filas[] = ['Total de grados', $this->resumen['total_grados'] ?? 0];
        $filas[] = ['Total de grupos', $this->resumen['total_grupos'] ?? 0];
        $filas[] = ['Materias distintas', $this->resumen['total_materias'] ?? 0];
        $filas[] = ['Promedio general', $this->formatear($this->resumen['promedio_general'] ?? null)];
        $filas[] = ['Tipo de promedio', ($this->resumen['promedio_definitivo'] ?? false) ? 'Definitivo' : 'Provisional'];
        $filas[] = ['Alumnos con captura completa', $this->resumen['alumnos_completos'] ?? 0];
        $filas[] = ['Alumnos con captura incompleta', $this->resumen['alumnos_incompletos'] ?? 0];

        $filas[] = [];
        $this->filasTitulo[] = count($filas) + 1;
        $filas[] = ['PROMEDIO POR GRADO'];
        $this->filasEncabezado[] = count($filas) + 1;
        $filas[] = ['Grado', 'Grupos', 'Alumnos', 'Completos', 'Incompletos', 'Promedio', 'Estado'];

        foreach ($this->grados as $grado) {
            $filas[] = [
                $grado['grado'] ?? 'Sin grado',
                $grado['total_grupos'] ?? 0,
                $grado['total_alumnos'] ?? 0,
                $grado['alumnos_completos'] ?? 0,
                $grado['alumnos_incompletos'] ?? 0,
                $this->formatear($grado['promedio_grado'] ?? null),
                ($grado['completo'] ?? false) ? 'Definitivo' : 'Provisional',
            ];
        }

        $filas[] = [];
        $this->filasTitulo[] = count($filas) + 1;
        $filas[] = ['PROMEDIO POR GRUPO'];
        $this->filasEncabezado[] = count($filas) + 1;
        $filas[] = ['Grado', 'Grupo', 'Generación', 'Alumnos', 'Completos', 'Incompletos', 'Promedio', 'Estado'];

        foreach ($this->grupos as $grupo) {
            $filas[] = [
                $grupo['grado'] ?? 'Sin grado',
                $grupo['grupo'] ?? 'Sin grupo',
                $grupo['generacion'] ?? 'Sin generación',
                $grupo['total_alumnos'] ?? 0,
                $grupo['alumnos_completos'] ?? 0,
                $grupo['alumnos_incompletos'] ?? 0,
                $this->formatear($grupo['promedio_grupo'] ?? null),
                ($grupo['completo'] ?? false) ? 'Definitivo' : 'Provisional',
            ];
        }

        $filas[] = [];
        $this->filasTitulo[] = count($filas) + 1;
        $filas[] = ['FÓRMULA'];
        $filas[] = ['Promedio final por materia', 'Suma de evaluaciones numéricas / cantidad de evaluaciones numéricas capturadas'];
        $filas[] = ['Promedio general del alumno', 'Suma de promedios de materias con valor numérico / cantidad de materias con promedio'];
        $filas[] = ['Promedio de grado o grupo', 'Suma de promedios generales disponibles / total de alumnos con promedio'];
        $filas[] = ['Nota', 'No se truncan resultados intermedios. El resultado se marca como provisional mientras existan evaluaciones pendientes.'];

        return $filas;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $hoja = $event->sheet->getDelegate();
                $ultimaFila = $hoja->getHighestRow();
                $ultimaColumna = 'H';

                $hoja->mergeCells("A1:{$ultimaColumna}1");
                $hoja->mergeCells("A2:{$ultimaColumna}2");

                $hoja->getStyle("A1:{$ultimaColumna}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006492']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $hoja->getStyle("A2:{$ultimaColumna}2")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '475569']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                foreach ($this->filasTitulo as $fila) {
                    $hoja->mergeCells("A{$fila}:{$ultimaColumna}{$fila}");
                    $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '88AC2E']],
                    ]);
                }

                foreach ($this->filasEncabezado as $fila) {
                    $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                }

                $hoja->getStyle("A1:{$ultimaColumna}{$ultimaFila}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                $hoja->getColumnDimension('A')->setWidth(30);
                $hoja->getColumnDimension('B')->setWidth(34);
                foreach (range('C', 'H') as $columna) {
                    $hoja->getColumnDimension($columna)->setWidth(18);
                }

                $hoja->freezePane('A4');
            },
        ];
    }

    private function formatear(null|int|float|string $valor): string
    {
        return PromedioExcel::formatear($valor, 1, '—');
    }
}
