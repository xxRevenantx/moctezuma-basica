<?php

namespace App\Exports;

use App\Support\CalificacionBachillerato;
use App\Support\PromedioExcel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PromediosMateriasDetalleAlumnosSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(protected array $reporte)
    {
        //
    }

    public function title(): string
    {
        return 'Detalle por alumno';
    }

    public function array(): array
    {
        $maxEvaluaciones = collect($this->reporte['alumnos'] ?? [])
            ->flatMap(fn (array $alumno) => $alumno['materias'] ?? [])
            ->map(fn (array $materia) => count($materia['evaluaciones_esperadas'] ?? []))
            ->max() ?: 3;

        $encabezado = ['Matrícula', 'Alumno', 'Grado', 'Semestre', 'Grupo', 'Materia', 'Campo formativo'];
        for ($i = 1; $i <= $maxEvaluaciones; $i++) {
            $encabezado[] = 'Evaluación ' . $i;
        }
        $encabezado = [...$encabezado, 'Suma', 'Promedio materia', 'Promedio general', 'Fuente externa', 'Estado'];

        $filas = [
            ['DETALLE POR ALUMNO Y MATERIA'],
            [($this->reporte['nivel']['nombre'] ?? 'Nivel') . ' · ' . ($this->reporte['ciclo']['texto'] ?? '—')],
            [],
            $encabezado,
        ];

        foreach ($this->reporte['alumnos'] ?? [] as $alumno) {
            foreach ($alumno['materias'] ?? [] as $materia) {
                $fila = [
                    $alumno['matricula'] ?? '—',
                    $alumno['alumno'] ?? '—',
                    $alumno['grado'] ?? '—',
                    $alumno['semestre'] ? 'Semestre ' . $alumno['semestre'] : '—',
                    $alumno['grupo'] ?? '—',
                    $materia['materia'] ?? '—',
                    $materia['campo_formativo'] ?? 'Sin campo formativo',
                ];

                $claves = $materia['evaluaciones_esperadas'] ?? [];
                for ($i = 0; $i < $maxEvaluaciones; $i++) {
                    $clave = $claves[$i] ?? null;
                    $valor = $clave !== null ? ($materia['evaluaciones'][$clave] ?? null) : null;
                    $especial = $clave !== null ? ($materia['especiales'][$clave] ?? null) : null;
                    $fila[] = $valor !== null ? $this->valorCalificacion($valor) : ($especial ?: '—');
                }

                $fila[] = $this->valorEntero($materia['suma'] ?? null);
                $fila[] = $this->valorCalificacion($materia['promedio'] ?? null);
                $fila[] = $this->valor($alumno['promedio_general'] ?? null);
                $fila[] = ($materia['tiene_calificacion_externa'] ?? false) ? 'Sí' : 'No';
                $fila[] = ($materia['provisional'] ?? true) ? 'Provisional' : 'Definitivo';
                $filas[] = $fila;
            }
        }

        return $filas;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $hoja = $event->sheet->getDelegate();
            $ultimaFila = $hoja->getHighestRow();
            $ultimaColumna = $hoja->getHighestColumn();
            $hoja->mergeCells("A1:{$ultimaColumna}1");
            $hoja->mergeCells("A2:{$ultimaColumna}2");
            $hoja->getStyle("A1:{$ultimaColumna}1")->applyFromArray([
                'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006492']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $hoja->getStyle("A4:{$ultimaColumna}4")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $hoja->getStyle("A1:{$ultimaColumna}{$ultimaFila}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            $hoja->freezePane('A5');
            $hoja->setAutoFilter("A4:{$ultimaColumna}{$ultimaFila}");
        }];
    }

    private function valor(mixed $valor): string
    {
        return PromedioExcel::formatear($valor, 1, '—');
    }

    private function valorCalificacion(mixed $valor): string
    {
        return ($this->reporte['es_bachillerato'] ?? false)
            ? CalificacionBachillerato::formatearEntero($valor)
            : PromedioExcel::formatear($valor, 1, '—');
    }

    private function valorEntero(mixed $valor): string
    {
        if ($valor === null || $valor === '' || ! is_numeric($valor)) {
            return '—';
        }

        return ($this->reporte['es_bachillerato'] ?? false)
            ? (string) ((int) floor((float) $valor + 0.000000001))
            : PromedioExcel::formatear($valor, 1, '—');
    }
}
