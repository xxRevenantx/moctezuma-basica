<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MatriculaExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected Collection $rows;
    protected string $nivelNombre;
    protected string $generacionNombre;
    protected string $grupoNombre;
    protected string $search;

    public function __construct(
        Collection $rows,
        string $nivelNombre = '—',
        string $generacionNombre = '—',
        string $grupoNombre = '—',
        string $search = ''
    ) {
        $this->rows = $rows;
        $this->nivelNombre = $nivelNombre;
        $this->generacionNombre = $generacionNombre;
        $this->grupoNombre = $grupoNombre;
        $this->search = trim($search);
    }

    public function collection()
    {
        return $this->rows->values()->map(function ($row, $index) {
            return [
                'no' => $index + 1,
                'id' => $row->id,
                'curp' => $row->curp ?? '—',
                'matricula' => $row->matricula ?? '—',
                'folio' => $row->folio ?? '—',
                'nombre' => $row->nombre ?? '—',
                'apellido_paterno' => $row->apellido_paterno ?? '—',
                'apellido_materno' => $row->apellido_materno ?? '—',
                'fecha_nacimiento' => $row->fecha_nacimiento ?? '—',
                'genero' => $row->genero ?? '—',
                'pais_nacimiento' => $row->pais_nacimiento ?? '—',
                'estado_nacimiento' => $row->estado_nacimiento ?? '—',
                'lugar_nacimiento' => $row->lugar_nacimiento ?? '—',
                'calle' => $row->calle ?? '—',
                'numero_exterior' => $row->numero_exterior ?? '—',
                'numero_interior' => $row->numero_interior ?? '—',
                'colonia' => $row->colonia ?? '—',
                'codigo_postal' => $row->codigo_postal ?? '—',
                'municipio' => $row->municipio ?? '—',
                'estado_residencia' => $row->estado_residencia ?? '—',
                'ciudad_residencia' => $row->ciudad_residencia ?? '—',
                'nivel_id' => $row->nivel_id ?? '—',
                'nivel' => $this->nivelNombre,
                'grado_id' => $row->grado_id ?? '—',
                'grado' => $row->grado?->nombre ?? '—',
                'generacion_id' => $row->generacion_id ?? '—',
                'generacion' => $row->generacion
                    ? ($row->generacion->anio_ingreso . ' - ' . $row->generacion->anio_egreso)
                    : '—',
                'grupo_id' => $row->grupo_id ?? '—',
                'grupo' => $row->grupo?->nombre ?? '—',
                'semestre_id' => $row->semestre_id ?? '—',
                'ciclo_id' => $row->ciclo_id ?? '—',
                'foto_path' => $row->foto_path ?? '—',
                'tutor_id' => $row->tutor_id ?? '—',
                'activo' => (int) ($row->activo ?? 0) === 1 ? 'Activo' : 'Inactivo',
                'fecha_inscripcion' => $row->fecha_inscripcion ?? '—',
                'created_at' => $row->created_at?->format('Y-m-d H:i:s') ?? '—',
                'updated_at' => $row->updated_at?->format('Y-m-d H:i:s') ?? '—',
                'deleted_at' => $row->deleted_at?->format('Y-m-d H:i:s') ?? '—',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No.',
            'ID',
            'CURP',
            'Matrícula',
            'Folio',
            'Nombre(s)',
            'Apellido paterno',
            'Apellido materno',
            'Fecha de nacimiento',
            'Género',
            'País de nacimiento',
            'Estado de nacimiento',
            'Lugar de nacimiento',
            'Calle',
            'Número exterior',
            'Número interior',
            'Colonia',
            'Código postal',
            'Municipio',
            'Estado de residencia',
            'Ciudad de residencia',
            'Nivel ID',
            'Nivel',
            'Grado ID',
            'Grado',
            'Generación ID',
            'Generación',
            'Grupo ID',
            'Grupo',
            'Semestre ID',
            'Ciclo ID',
            'Foto path',
            'Tutor ID',
            'Activo',
            'Fecha inscripción',
            'Creado',
            'Actualizado',
            'Eliminado',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $totalDatos = $this->rows->count();
                $ultimaColumna = 'AL';
                $ultimaFila = $totalDatos + 5;

                $sheet->insertNewRowBefore(1, 4);

                $sheet->mergeCells("A1:{$ultimaColumna}1");
                $sheet->setCellValue('A1', 'Matrícula completa de alumnos');

                $sheet->mergeCells("A2:{$ultimaColumna}2");
                $sheet->setCellValue(
                    'A2',
                    'Nivel: ' . $this->nivelNombre .
                    ' | Generación: ' . $this->generacionNombre .
                    ' | Grupo: ' . $this->grupoNombre
                );

                $sheet->mergeCells("A3:{$ultimaColumna}3");
                $sheet->setCellValue(
                    'A3',
                    'Búsqueda: ' . ($this->search !== '' ? $this->search : 'Sin filtro de búsqueda')
                );

                $sheet->mergeCells("A4:{$ultimaColumna}4");
                $sheet->setCellValue('A4', 'Total de alumnos exportados: ' . $totalDatos);

                $sheet->getStyle("A1:{$ultimaColumna}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 15,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0284C7'],
                    ],
                ]);

                $sheet->getStyle("A2:{$ultimaColumna}4")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 10,
                        'color' => ['rgb' => '1E293B'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E0F2FE'],
                    ],
                ]);

                $sheet->getStyle("A5:{$ultimaColumna}5")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0F172A'],
                    ],
                ]);

                $sheet->getStyle("A5:{$ultimaColumna}{$ultimaFila}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                ]);

                $sheet->freezePane('A6');
            },
        ];
    }
}
