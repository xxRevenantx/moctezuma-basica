<?php

namespace App\Exports;

use App\Http\Controllers\FichaController;
use App\Models\CicloEscolar;
use App\Models\Inscripcion;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class FichaDescriptivaPlantillaImportacionExport implements FromArray, WithHeadings, ShouldAutoSize, WithTitle, WithEvents
{
    public function __construct(
        private readonly int $nivelId,
        private readonly int $gradoId,
        private readonly ?int $grupoId,
        private readonly ?int $generacionId,
        private readonly int $cicloEscolarId,
        private readonly int $periodo
    ) {
    }

    public function title(): string
    {
        return 'IMPORTAR FICHAS';
    }

    public function headings(): array
    {
        return [
            'ID_INSCRIPCION',
            'MATRICULA',
            'CURP',
            'ALUMNO',
            'NIVEL',
            'GRADO',
            'GRUPO',
            'CICLO_ESCOLAR_ID',
            'PERIODO',
            'CAMPO_LENGUAJES',
            'CAMPO_SABERES',
            'CAMPO_ETICA',
            'CAMPO_HUMANO',
            'RECOMENDACIONES',
        ];
    }

    public function array(): array
    {
        $ciclo = CicloEscolar::query()->find($this->cicloEscolarId);

        return Inscripcion::query()
            ->with([
                'nivel:id,nombre',
                'grado:id,nombre',
                'grupo.asignacionGrupo:id,nombre',
            ])
            ->where('nivel_id', $this->nivelId)
            ->where('grado_id', $this->gradoId)
            ->when($this->grupoId, fn($q) => $q->where('grupo_id', $this->grupoId))
            ->when($this->generacionId, fn($q) => $q->where('generacion_id', $this->generacionId))
            ->where('activo', true)
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get()
            ->map(function (Inscripcion $alumno) use ($ciclo) {
                $nombreAlumno = trim(
                    ($alumno->apellido_paterno ?? '') . ' ' .
                    ($alumno->apellido_materno ?? '') . ' ' .
                    ($alumno->nombre ?? '')
                );

                return [
                    $alumno->id,
                    $alumno->matricula,
                    $alumno->curp,
                    $nombreAlumno,
                    $alumno->nivel?->nombre,
                    $alumno->grado?->nombre,
                    $alumno->grupo?->asignacionGrupo?->nombre ?? 'S/G',
                    $this->cicloEscolarId,
                    $this->periodo,
                    '',
                    '',
                    '',
                    '',
                    '',
                ];
            })
            ->toArray();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $ultimaColumna = 'N';

                $sheet->getStyle("A1:{$ultimaColumna}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => '006492'],
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(38);

                foreach (range('A', $ultimaColumna) as $columna) {
                    $sheet->getStyle($columna)->getAlignment()->setVertical('top');
                    $sheet->getStyle($columna)->getAlignment()->setWrapText(true);
                }

                $sheet->getColumnDimension('A')->setWidth(14);
                $sheet->getColumnDimension('B')->setWidth(16);
                $sheet->getColumnDimension('C')->setWidth(22);
                $sheet->getColumnDimension('D')->setWidth(38);
                $sheet->getColumnDimension('E')->setWidth(18);
                $sheet->getColumnDimension('F')->setWidth(16);
                $sheet->getColumnDimension('G')->setWidth(12);
                $sheet->getColumnDimension('H')->setWidth(16);
                $sheet->getColumnDimension('I')->setWidth(10);

                foreach (['J', 'K', 'L', 'M', 'N'] as $columna) {
                    $sheet->getColumnDimension($columna)->setWidth(48);
                }

                $sheet->freezePane('J2');

                $highestRow = max($sheet->getHighestRow(), 2);

                $sheet->getStyle("A1:{$ultimaColumna}{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => 'thin',
                            'color' => ['rgb' => 'D9D9D9'],
                        ],
                    ],
                ]);

                $sheet->getStyle("A2:I{$highestRow}")->applyFromArray([
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => 'F2F2F2'],
                    ],
                    'font' => [
                        'color' => ['rgb' => '555555'],
                    ],
                ]);

                $sheet->getStyle("J2:N{$highestRow}")->applyFromArray([
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => 'FFFFFF'],
                    ],
                ]);

                $sheet->setAutoFilter("A1:{$ultimaColumna}1");

                $sheet->getProtection()->setSheet(true);

                foreach (['J', 'K', 'L', 'M', 'N'] as $columna) {
                    $sheet->getStyle("{$columna}2:{$columna}{$highestRow}")
                        ->getProtection()
                        ->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
                }

                $sheet->getStyle("A1:I{$highestRow}")
                    ->getProtection()
                    ->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_PROTECTED);

                $sheet->getStyle("J2:N{$highestRow}")
                    ->getAlignment()
                    ->setWrapText(true);

                $sheet->getStyle("J2:N{$highestRow}")
                    ->getAlignment()
                    ->setVertical('top');
            },
        ];
    }
}
